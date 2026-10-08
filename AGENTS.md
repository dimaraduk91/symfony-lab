# Symfony Lab — Codex Instructions

## Purpose and collaboration

This is an incremental backend engineering lab and interview portfolio. The owner
is a senior backend engineer experienced in PHP and Node.js/AWS, learning Go and
distributed systems through implementation and experiments.

Communicate in Russian unless the user uses or requests English. Keep code,
identifiers, technical documentation and commit messages in English. Be practical
and direct; explain unfamiliar trade-offs without beginner tutorials. Prefer
simple, maintainable solutions and challenge unnecessary complexity.

## Mandatory incremental workflow

- Read `docs/roadmap.md` before architectural work. It is a roadmap, not permission
  to implement everything. Work only on the task explicitly selected by the user.
- If no task was selected, clarify before implementing functionality. The owner
  may implement parts personally; do not preempt their work.
- Inspect relevant code, configuration, tests and documentation first.
- Implement the selected scope, verify it, explain the result and stop. Never
  automatically advance to the next task or stage.
- Aim for tasks that can be understood and verified in one or two evenings.
- At handoff, state changes, important decisions, verification and limitations.
  Suggest the next task without starting it.
- Record approved completion evidence in the roadmap; code existing is not proof
  that its acceptance criteria passed.

## Current state

- PHP 8.x, Symfony 7.4, Doctrine ORM/DBAL and PostgreSQL.
- nginx proxies HTTP to RoadRunner. PHP-FPM remains available for CLI work and
  runtime comparisons. Preserve the comparison path.
- Ordering creates and queries orders, with idempotency and transactional outbox.
- The existing outbox publishes through Symfony Messenger to RabbitMQ.
- Kafka has a separate demonstration publisher and PHP analytics/audit consumers.
  Real order creation is not yet wired to Kafka.
- The working tree includes an observability baseline: Prometheus, Grafana,
  OpenTelemetry Collector, Tempo, Redis-backed PHP metrics and JSON logs.
  Check its actual verification and Git status before relying on it.
- `/health` is liveness and must not depend on external services. `/ready` is
  readiness and currently checks PostgreSQL. Preserve this distinction.

Go Inventory, Go Analytics, gRPC, centralized log storage and multi-service
scaling are planned, not implemented. Do not add them unless selected.

## Engineering conventions

- Keep changes focused; no unrelated refactoring, moves or renames.
- Use strict PHP typing, constructor injection and immutable dependencies where
  appropriate. Keep controllers thin and non-trivial business logic outside them.
- Follow existing Symfony and language conventions. Avoid service locators,
  direct container access, hidden global state and speculative abstractions.
- Prefer existing dependencies. Explain a concrete benefit before adding one.
- SQL and Doctrine DBAL are welcome; do not replace explicit SQL with ORM merely
  for uniformity. Consider indexes, constraints, query plans and transactions.
- Consider concurrency, idempotency, retries, timeouts, API compatibility,
  failure recovery, security and observability as relevant.
- RoadRunner workers persist: clean up request/trace context and avoid mutable
  process-local state leaking between requests.
- Preserve behavior unless the selected task explicitly changes it.

## Infrastructure and messaging

Keep the project runnable with Docker Compose. Prefer PHP, Composer and Symfony
commands inside the application container when that matches the runtime.
Do not expose PostgreSQL publicly or change secrets/environment values without
explicit authorization. Existing configuration is not a production template.

RabbitMQ handles jobs/commands; Kafka handles durable event streams and
independent consumers/replay. They are not interchangeable. Each new technology
needs a concrete use case and a verifiable result.

## Verification

Discover commands from the repository. Run relevant tests and checks: PHP syntax,
Symfony container/config validation, endpoints, migrations and database
connectivity where applicable. For Go, use appropriate unit, integration and race
checks. Do not claim unverified behavior works. Report checks that could not run.

In reviews, prioritize correctness, data consistency, security, concurrency and
material performance issues; reference exact code when possible.

## Git and publication

- Do not create branches, commit, push, merge, deploy or publish unless explicitly
  requested. Do not rewrite history except for a specifically authorized operation.
- Preserve unrelated local changes. Never stage the entire working tree blindly.
- The one-time authorized publication preparation filters personal notes from a
  separate copy; it does not authorize rewriting the original repository.
- Before the first push, use the verified cleaned copy, not the original history.
  Local handoff details are in `.local/publication/` when present.
- Never commit personal notes, local backups or unapproved documentation drafts.

## Documentation approval

- `docs/roadmap.md` is the approved direction. Initial creation is authorized;
  its existence does not approve future implementation or documentation changes.
- `docs/` and README are public-facing. New or materially revised agent-written
  documentation requires owner review and approval before staging or committing.
  Existing untracked documents are not implicitly approved.
- Keep drafts and personal notes in ignored `study/`, with publication drafts in
  `study/drafts/`. Never link public documentation to local-only notes or copy
  private notes into public documents without explicit approval.
- Keep this file focused on stable rules; put detailed plans and approved
  experiment reports in `docs/`.
