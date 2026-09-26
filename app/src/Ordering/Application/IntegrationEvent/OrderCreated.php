<?php

declare(strict_types=1);

namespace App\Ordering\Application\IntegrationEvent;

final readonly class OrderCreated
{
    public const type = 'order.created.v1';

    public function __construct(
        public string $eventId,
        public string $orderId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
