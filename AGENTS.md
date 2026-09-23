# Symfony Lab — Codex Instructions

## Project purpose

This repository is a backend engineering lab built around Symfony.

The goal is not only to implement features, but also to explore and demonstrate production-oriented backend practices that are useful for senior backend engineering interviews and real-world systems.

The project should evolve incrementally.

Current stack:

* PHP 8.x
* Symfony 7.x
* PostgreSQL
* Docker / Docker Compose
* nginx
* PHP-FPM

Planned later:

* RoadRunner
* RabbitMQ
* Kafka
* background workers
* metrics and monitoring
* load testing
* distributed-system patterns where appropriate

Do not introduce planned technologies prematurely unless the current task explicitly requires them.

---

## Current state

The application currently exposes:

* `GET /health`

    * verifies that the application process is alive
    * must not depend on PostgreSQL or other external services

* `GET /ready`

    * verifies that the application is ready to serve requests
    * currently checks PostgreSQL connectivity

Preserve the semantic distinction between liveness and readiness.

---

## Engineering principles

Prefer simple, explicit solutions over unnecessary abstractions.

Do not introduce architecture only because it may be useful later.

Apply abstractions when there is an actual use case.

Keep controllers thin.

Business logic should live outside controllers when it becomes non-trivial.

Prefer constructor dependency injection.

Prefer immutable dependencies and `readonly` where appropriate.

Use strict typing in PHP.

Follow Symfony conventions unless there is a clear reason not to.

Avoid service locator patterns and direct container access.

Avoid hidden global state.

Use PostgreSQL-specific capabilities when they provide a meaningful benefit.

SQL and Doctrine DBAL are acceptable and encouraged when explicit SQL makes the behavior clearer.

Do not automatically replace explicit SQL with ORM abstractions.

When changing database behavior, consider:

* indexes
* constraints
* transactions
* isolation/concurrency
* query plans
* failure cases

---

## Architecture direction

The project should gradually demonstrate:

HTTP API
→ application/service layer
→ PostgreSQL
→ async jobs
→ message broker
→ workers
→ observability

The expected evolution is approximately:

PHP-FPM + nginx
→ baseline measurements
→ RoadRunner
→ comparison with PHP-FPM
→ RabbitMQ
→ event-driven workflows
→ Kafka where Kafka provides a real architectural benefit
→ metrics / tracing / monitoring
→ load testing and performance analysis

Do not skip directly to the final architecture.

Each stage should remain runnable and understandable.

---

## RoadRunner

RoadRunner is intentionally postponed.

Until a task explicitly introduces RoadRunner:

* keep PHP-FPM working
* do not add RoadRunner dependencies
* do not write code that requires long-running workers
* avoid relying on process-local mutable state

When RoadRunner is introduced, preserve the PHP-FPM implementation long enough to allow meaningful performance comparison.

---

## Messaging

RabbitMQ and Kafka serve different purposes in this project.

Do not treat them as interchangeable technologies.

RabbitMQ should initially be considered for:

* background jobs
* commands
* task queues
* retry/dead-letter workflows

Kafka should only be introduced when the project has a meaningful event-streaming use case, such as:

* durable event streams
* multiple independent consumers
* replay
* event-driven analytics
* event history

Do not add Kafka merely to demonstrate that Kafka can be installed.

---

## Docker

The application should remain runnable through Docker Compose.

Prefer executing PHP, Composer and Symfony commands inside the application container when that reflects the real runtime environment.

Do not expose PostgreSQL publicly unless a task explicitly requires it.

Keep infrastructure configuration understandable and minimal.

---

## Code changes

Before making changes:

1. inspect the relevant existing code;
2. understand the current architecture;
3. check `composer.json`, Docker configuration and relevant Symfony configuration;
4. reuse existing conventions where reasonable.

Do not perform unrelated refactoring.

Do not rename or move unrelated files.

Do not introduce dependencies without explaining why they are required.

When several implementations are reasonable, prefer the simplest implementation that keeps the next architectural step possible.

---

## Verification

After implementation, perform the relevant verification available in the repository.

At minimum consider:

* PHP syntax
* Symfony container/config validation
* tests
* relevant endpoint behavior
* database connectivity where applicable

Discover the actual available commands from the repository instead of inventing commands that are not configured.

If a check cannot be run, explicitly state that.

---

## Git

Do not create branches unless explicitly requested.

Do not commit changes unless explicitly requested.

Do not rewrite existing Git history.

Keep changes focused on the requested task.

---

## Working style

For implementation tasks:

1. inspect the existing implementation;
2. briefly state the proposed change;
3. implement it;
4. run relevant checks;
5. summarize:

    * what changed;
    * important architectural decisions;
    * verification performed;
    * remaining risks or follow-up work.

For larger architectural tasks, do not implement everything at once.

Break the work into small independently verifiable stages.

If the user's proposal introduces unnecessary complexity, point it out and suggest a simpler alternative.

---

## Documentation

Treat repository documentation as the source of truth for architectural decisions.

When present, read relevant files from `docs/` before making architectural changes.

Keep `AGENTS.md` compact and focused on stable project-wide rules.

Put detailed plans, experiments and architectural notes in `docs/`.
