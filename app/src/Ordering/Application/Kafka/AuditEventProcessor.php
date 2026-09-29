<?php

declare(strict_types=1);

namespace App\Ordering\Application\Kafka;

use Doctrine\DBAL\Connection;

final readonly class AuditEventProcessor
{
    public function __construct(private Connection $connection) {}

    public function process(OrderEventEnvelope $event): bool
    {
        return $this->connection->executeStatement(
            'INSERT INTO order_event_audit (event_id, event_type, aggregate_id, occurred_at, payload, processed_at) VALUES (?, ?, ?, ?, ?::jsonb, CURRENT_TIMESTAMP) ON CONFLICT DO NOTHING',
            [$event->eventId, $event->eventType, $event->aggregateId, $event->occurredAt->format('Y-m-d H:i:sP'), json_encode($event->payload, JSON_THROW_ON_ERROR)],
        ) === 1;
    }
}
