<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Outbox;

use App\Ordering\Application\IntegrationEvent\OrderCreated;
use App\Ordering\Application\Outbox\Outbox;
use Doctrine\DBAL\Connection;

final readonly class DoctrineOutbox implements Outbox
{
    public function __construct(private Connection $connection) {}

    public function add(OrderCreated $event): void
    {
        $this->connection->insert('outbox_messages', [
            'id' => $event->eventId,
            'type' => $event::type,
            'payload' => json_encode([
                'eventId' => $event->eventId,
                'orderId' => $event->orderId,
                'occurredAt' => $event->occurredAt->format(DATE_ATOM),
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:sP'),
        ]);
    }
}
