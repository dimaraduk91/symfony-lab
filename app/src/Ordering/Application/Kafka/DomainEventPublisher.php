<?php

declare(strict_types=1);

namespace App\Ordering\Application\Kafka;

interface DomainEventPublisher
{
    /** @param array<string, mixed> $payload */
    public function publish(string $eventId, string $eventType, string $aggregateId, \DateTimeImmutable $occurredAt, array $payload = []): void;
}
