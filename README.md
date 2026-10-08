# Symfony Lab

## RoadRunner

HTTP requests are handled by nginx and proxied to RoadRunner. The `php` service
keeps PHP-FPM available for CLI commands and later FPM/RoadRunner comparisons.

Install dependencies and the platform-specific RoadRunner binary once, then
start the stack:

```bash
make install
make rr-install
make build
```

For subsequent starts use `make up`. The application is available at
<http://localhost:8080>.

RoadRunner is also published directly on <http://localhost:8081>. The direct
start target stops nginx and PHP-FPM, starts RoadRunner and its dependencies,
then the benchmark sends traffic straight to RoadRunner:

```bash
make rr-direct-up
make rr-benchmark
```

The benchmark target passes `BASE_URL=http://localhost:8081` to k6. Running
`k6 run benchmark.js` without that variable keeps using nginx on port 8080.

Useful commands:

```bash
make rr-version
make rr-logs
make down
```

## Observability

The local stack includes Prometheus, Grafana, OpenTelemetry Collector, Tempo,
PostgreSQL exporter, RabbitMQ native metrics, JSON application logs and
request/trace correlation. See [docs/observability.md](docs/observability.md)
for architecture and operational reference. The approved incremental development
direction is in [docs/roadmap.md](docs/roadmap.md).

## Kafka Lab

Kafka is used here as a durable `order.events` domain-event stream. RabbitMQ remains the transport for asynchronous commands/jobs: Kafka is not simply RabbitMQ with higher throughput, and it does not replace RabbitMQ in this project.

Start the stack, install the new PHP extension dependency metadata, and migrate:

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

The KRaft broker is internal to the Compose network; no host port is published. `kafka-init` creates `order.events` with three partitions and replication factor 1.

### Core model

- **Topic:** `order.events` stores Order domain events independently of whether any consumer processed them.
- **Partition:** ordering is guaranteed within one partition, not across the entire topic. The producer uses `orderId`/`aggregateId` as the Kafka message key, so events for one Order consistently reach the same partition.
- **Consumer group:** consumers in the same group divide partitions/work; different groups independently receive the full stream. Only one consumer in a group actively consumes a given partition, so a fourth analytics consumer is idle with only three partitions.
- **Offset:** an offset is a consumer group's progress within a partition. Consumers disable auto-commit, process the database transaction first, then explicitly commit the message offset.
- **At-least-once:** a crash after the database commit but before the Kafka commit causes redelivery. Consumers must tolerate duplicates.
- **Idempotency:** analytics inserts `(event_id, consumer_name)` into `processed_kafka_events` and updates `order_event_statistics` in one PostgreSQL transaction. The primary key makes a duplicate a no-op, so it cannot increment statistics twice.
- **Replay:** Kafka retention is independent of consumption. Stop consumers, clear the projection, and reset group offsets to rebuild it.

The demo publisher emits `OrderCreated`, `OrderPaid`, and `OrderCancelled` for four orders. All three events for an order share its key:

```bash
docker compose exec php php bin/console app:kafka:publish-demo-events
```

### Kafka CLI

```bash
# list and describe topics/partitions
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server kafka:9092 --list
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server kafka:9092 --describe --topic order.events

# terminal consumer: print partition, offset and key
docker compose exec kafka /opt/kafka/bin/kafka-console-consumer.sh --bootstrap-server kafka:9092 --topic order.events --from-beginning --property print.partition=true --property print.offset=true --property print.key=true

# groups, assignments and offsets
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh --bootstrap-server kafka:9092 --list
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh --bootstrap-server kafka:9092 --describe --group order-analytics
```

### Experiment 1: partitions and keys

Start the terminal consumer above, then run the demo publisher.
Compare its key, partition, and offset output.
Every record with the same `order-N` key stays in one partition and its offset increases in that partition; there is no global ordering across partitions.

### Experiment 2: consumer-group scaling

Run these in separate terminals:

```bash
docker compose exec php php bin/console app:kafka:consume-analytics
docker compose exec php php bin/console app:kafka:consume-analytics
```

The rebalance messages show the three partitions divided between the two instances.
Start up to four identical commands to observe that one instance receives no partition when four consumers compete for three partitions.
Publish more demo events after each rebalance.

### Experiment 3: independent groups

Run one command per terminal, then publish demo events:

```bash
docker compose exec php php bin/console app:kafka:consume-analytics
docker compose exec php php bin/console app:kafka:consume-audit
docker compose exec php php bin/console app:kafka:publish-demo-events
```

Both `order-analytics` and `order-audit` receive the full stream independently. Within either group, instances still share partitions.

### Experiment 4: deterministic duplicate delivery

Stop other analytics consumers. Publish events, then run:

```bash
docker compose exec php php bin/console app:kafka:consume-analytics --fail-after-processing
docker compose exec db psql -U symfony -d symfony -c "TABLE order_event_statistics;"
docker compose exec php php bin/console app:kafka:consume-analytics
```

The first command exits after its PostgreSQL transaction and before offset commit.
Restart without the flag: Kafka redelivers the same event and the log includes `duplicate=ignored`; its statistics count is unchanged.
The option intentionally fails every invocation, so remove it for the restart.

### Experiment 5: replay analytics

Stop all analytics consumers. Clear both the projection and its idempotency markers (otherwise replay is correctly treated as duplicate), reset offsets, and restart:

```bash
docker compose exec db psql -U symfony -d symfony -c "TRUNCATE order_event_statistics; DELETE FROM processed_kafka_events WHERE consumer_name = 'order-analytics';"
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh --bootstrap-server kafka:9092 --group order-analytics --topic order.events --reset-offsets --to-earliest --execute
docker compose exec php php bin/console app:kafka:consume-analytics
```

Kafka requires the group to be inactive for offset reset. Inspect the rebuilt projection and progress:

```bash
docker compose exec db psql -U symfony -d symfony -c "TABLE order_event_statistics;"
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh --bootstrap-server kafka:9092 --describe --group order-analytics
```

This lab publishes synthetic events directly for clarity. Connecting the existing transactional outbox to both RabbitMQ and Kafka needs an explicit multi-destination delivery design; doing that naively would mark an outbox row published after only one destination succeeded.
