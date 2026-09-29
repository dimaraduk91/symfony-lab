<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Application\Kafka;

use App\Ordering\Application\Kafka\OrderEventEnvelope;
use PHPUnit\Framework\TestCase;

final class OrderEventEnvelopeTest extends TestCase
{
    public function testItRoundTripsStableEnvelope(): void
    {
        $event = new OrderEventEnvelope(
            '0199-0000-7000-8000-000000000001',
            'OrderPaid',
            'order-42',
            new \DateTimeImmutable('2026-09-26T19:30:00+00:00'),
            ['amount' => 1200],
        );

        self::assertSame([
            'eventId' => '0199-0000-7000-8000-000000000001',
            'eventType' => 'OrderPaid',
            'aggregateId' => 'order-42',
            'occurredAt' => '2026-09-26T19:30:00+00:00',
            'payload' => ['amount' => 1200],
        ], json_decode($event->toJson(), true, 512, JSON_THROW_ON_ERROR));

        self::assertEquals($event, OrderEventEnvelope::fromJson($event->toJson()));
    }
}
