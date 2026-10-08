# Observability baseline

## Architecture

```text
Symfony / RoadRunner
 ├── RED + outbox metrics → Redis registry → /metrics → Prometheus → Grafana
 ├── traces → OTLP/HTTP → OpenTelemetry Collector → OTLP/gRPC → Tempo → Grafana
 └── JSON logs → stdout/stderr → docker compose logs

PostgreSQL → postgres_exporter ─┐
RabbitMQ → rabbitmq_prometheus ─┴→ Prometheus
```

nginx accepts HTTP traffic on port `8080` and proxies it to RoadRunner. The
`php` container remains available for CLI commands and the FPM/RoadRunner
comparison. RoadRunner runs persistent Symfony workers using
`Baldinof\RoadRunnerBundle\Runtime\Runtime`.

Order creation remains unchanged at the domain/application level: the HTTP DTO
is handled by `CreateOrderHandler`, which persists the aggregate and an
`OrderCreated` outbox row in the same PostgreSQL transaction. The outbox
publisher locks unpublished rows with `FOR UPDATE SKIP LOCKED`, sends them to
the RabbitMQ-backed `events` Messenger transport, and marks them published.
Observability metadata is stored in nullable outbox columns; it is not added to
domain entities or integration-event payloads.

Loki is deliberately not included. JSON container logs are sufficient for this
baseline and can be inspected with Docker. Grafana cannot query those logs or
offer a logs-to-trace link without a log datasource. Loki is a separate future
step when centralized log retention and search become an actual requirement.

## Start

Build is required after this change because the PHP image gains `ext-redis`:

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose ps
```

URLs:

| Component                    | URL                              | Credentials           |
|------------------------------|----------------------------------|-----------------------|
| Application                  | <http://localhost:8080>          | none                  |
| RoadRunner direct            | <http://localhost:8081>          | none                  |
| Grafana                      | <http://localhost:3000>          | `admin` / `admin`     |
| Prometheus                   | <http://localhost:9090>          | none                  |
| RabbitMQ Management          | <http://localhost:15672>         | `symfony` / `symfony` |
| RabbitMQ Prometheus endpoint | <http://localhost:15692/metrics> | none                  |

Grafana provisions the Prometheus and Tempo datasources and the
`Symfony / RoadRunner Overview` dashboard at startup.

## Application metrics

`GET /metrics` exposes:

| Metric                              | Type      | Labels                      | Meaning                                                                                   |
|-------------------------------------|-----------|-----------------------------|-------------------------------------------------------------------------------------------|
| `http_requests_total`               | counter   | `method`, `route`, `status` | Completed HTTP requests. `route` is the normalized Symfony route name, never the raw URL. |
| `http_request_duration_seconds`     | histogram | `method`, `route`           | Request duration with buckets from 5 ms to 5 s.                                           |
| `outbox_messages_pending`           | gauge     | none                        | Unpublished outbox rows.                                                                  |
| `outbox_oldest_message_age_seconds` | gauge     | none                        | Age of the oldest unpublished row; zero when the outbox is empty.                         |

The outbox gauges execute one indexed aggregate query when Prometheus scrapes
`/metrics`; they are not updated on the write path.

The Prometheus PHP registry uses Redis because RoadRunner workers are separate
persistent processes. In-memory or per-process APCu aggregation would make the
scraped result depend on which worker served `/metrics`. Redis is configured as
an ephemeral metrics state store; Prometheus remains the time-series store.

No `messenger_messages_processed_total` or `messenger_messages_failed_total`
was added. RabbitMQ already provides queue, publish, delivery and consumer
metrics without duplicating broker state in Symfony. A future application-level
handler outcome metric would have different semantics and should be introduced
only when that distinction is needed.

Standard exporters provide infrastructure metrics:

- `postgres_exporter`: connections by state, transactions, locks and other
  PostgreSQL collector metrics;
- RabbitMQ's built-in `rabbitmq_prometheus` plugin: ready/unacked messages,
  consumers, published and delivered counters.

## Verify metrics and targets

```bash
curl -s http://localhost:8080/health
curl -s http://localhost:8080/metrics | grep -E 'http_request|outbox_'
curl -s http://localhost:9090/api/v1/targets
curl -s 'http://localhost:9090/api/v1/query?query=up'
```

The expected Prometheus jobs are `symfony`, `postgres`, `rabbitmq`, and
`prometheus`, all with `up == 1`.

Run the test suite against an initialized test database:

```bash
docker compose exec php php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec php php bin/phpunit
```

Useful PromQL:

```promql
# Total RPS
sum(rate(http_requests_total[1m]))

# RPS by normalized route
sum by (route) (rate(http_requests_total[1m]))

# 5xx percentage
100 * sum(rate(http_requests_total{status=~"5.."}[5m]))
  / clamp_min(sum(rate(http_requests_total[5m])), 0.000001)

# p50 / p90 / p95 / p99; replace the quantile as needed
histogram_quantile(
  0.95,
  sum(rate(http_request_duration_seconds_bucket[5m])) by (le)
)

# Per-route p95
histogram_quantile(
  0.95,
  sum by (le, route) (rate(http_request_duration_seconds_bucket[5m]))
)

outbox_messages_pending
outbox_oldest_message_age_seconds
sum(pg_stat_activity_count{datname="symfony", state="active"})
sum(rabbitmq_queue_messages_ready)
sum(rabbitmq_queue_messages_unacked)
sum(rabbitmq_queue_consumers)
sum(rate(rabbitmq_channel_messages_published_total[1m]))
sum(rate(rabbitmq_channel_messages_delivered_total[1m]))
```

## Request ID and structured logs

For every main HTTP request the application accepts `X-Request-ID` only when it
matches `[A-Za-z0-9][A-Za-z0-9._:-]{0,127}`. Missing or invalid values are
replaced with UUIDv7. The ID is returned in the response and included in
Monolog's JSON `extra.request_id` field.

The log processor also adds `extra.trace_id` and `extra.span_id` while an active
span exists. View logs with:

```bash
docker compose logs -f roadrunner php
```

Both dev and prod stream JSON to stderr. Secrets and high-cardinality IDs are
log fields only; request IDs, trace IDs, UUIDs and raw URLs are never Prometheus
labels.

## Tracing and async propagation

The HTTP subscriber extracts an incoming W3C `traceparent`/`tracestate` or
starts a new SERVER span, then names it with the normalized Symfony route.
OpenTelemetry exports spans over OTLP/HTTP to the Collector, which batches and
forwards them over OTLP/gRPC to Tempo.

When an outbox row is written, the current W3C carrier and request ID are stored
with it. `OutboxPublisher` restores them as a Messenger stamp. Messenger
middleware creates a PRODUCER span, injects its W3C context into the serialized
stamp, and the consumer extracts it to create a CONSUMER span. This preserves:

```text
POST /api/orders → outbox row → RabbitMQ publish → Messenger consumer
```

To generate a trace, first ensure referenced rows exist:

```bash
docker compose exec db psql -U symfony -d symfony -c "
INSERT INTO customers (id,email,created_at) VALUES ('00000000-0000-7000-8000-000000000001','trace@example.test',CURRENT_TIMESTAMP) ON CONFLICT DO NOTHING;
INSERT INTO products (id,title,created_at) VALUES ('00000000-0000-7000-8000-000000000002','Trace product',CURRENT_TIMESTAMP) ON CONFLICT DO NOTHING;
INSERT INTO sellers (id,name,created_at) VALUES ('00000000-0000-7000-8000-000000000003','Trace seller',CURRENT_TIMESTAMP) ON CONFLICT DO NOTHING;"

curl -i http://localhost:8080/api/orders \
  -X POST \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: observability-demo-1' \
  -H 'X-Request-ID: observability-demo-1' \
  --data '{"customerId":"00000000-0000-7000-8000-000000000001","currency":"USD","items":[{"productId":"00000000-0000-7000-8000-000000000002","sellerId":"00000000-0000-7000-8000-000000000003","quantity":1,"price":1999}]}'

docker compose exec php php bin/console app:outbox:publish --limit=10
docker compose exec php php bin/console messenger:consume events --limit=1 -vv
```

Open Grafana, then **Explore → Tempo**, select `Service Name = symfony-lab`, and
run the TraceQL query. The HTTP, producer and consumer spans share one trace.
The HTTP response's request ID can be matched to JSON log fields.

The implementation intentionally uses stable manual HTTP/Messenger
instrumentation rather than PHP extension-based auto-instrumentation. Therefore
Doctrine queries do not yet have automatic child spans. This avoids relying on
runtime hooks with ambiguous lifecycle behavior under persistent RoadRunner
workers. Add Doctrine auto-instrumentation later only after verifying its
context cleanup and overhead under the project's exact runtime.

## RoadRunner lifecycle safety

- Metrics are aggregated in Redis, not mutable worker-local memory.
- Request ID state is cleared before every main request and after response or
  finish-request fallback.
- Every HTTP and Messenger scope is detached in deterministic cleanup code and
  every span is ended.
- Messenger consumer correlation state is cleared before and after handling.
- The OTLP exporter uses the simple processor for deterministic lab visibility.
  This exports synchronously and is not the throughput-optimal production
  choice; benchmark its overhead before comparing runtimes. A batch processor
  is the next step once flush behavior is validated for long-running workers.

## Short load test

The existing k6 benchmark accepts overrides, so a short dashboard smoke test is:

```bash
k6 run -e BASE_URL=http://localhost:8080 -e RATE=50 -e DURATION=20s \
  -e PRE_ALLOCATED_VUS=20 -e MAX_VUS=100 benchmark.js
```

Open the dashboard before running it and use a last-15-minutes time range.

## Example SLI/SLO

Availability SLI is the ratio of non-5xx responses to all measured HTTP
responses. A reasonable learning SLO is **99.9% successful requests over a
rolling 30-day window**. Its error budget is 0.1%, approximately 43 minutes and
50 seconds of total unavailability in a 30-day month if failures are treated as
complete downtime.

Latency SLI is the percentage of requests completed below 300 ms. An example
SLO is **95% of requests below 300 ms over 30 days**. This differs from saying
that p95 should occasionally be below 300 ms: the SLI should be evaluated over
the whole SLO window and normally segmented by meaningful route/class of work.

No alert manager or SLO management platform is included in this baseline.

## Known limitations and follow-ups

- Logs are JSON stdout/stderr only; Grafana log search and logs-to-traces links
  require a future Loki (or another log datasource).
- No automatic Doctrine spans and no RoadRunner internal worker/restart/memory
  metrics are exposed yet.
- Redis has no persistence because losing current counters during a local stack
  restart is acceptable; Prometheus owns historical samples.
- The outbox aggregate query is cheap with the existing partial index at this
  scale. Revisit it only if scrape-time query cost becomes visible.
- Grafana uses lab credentials (`admin` / `admin`); do not reuse this Compose
  configuration as a public deployment.
