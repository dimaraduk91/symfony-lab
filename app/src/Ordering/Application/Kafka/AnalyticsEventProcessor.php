<?php

declare(strict_types=1);

namespace App\Ordering\Application\Kafka;

use Doctrine\DBAL\Connection;

final readonly class AnalyticsEventProcessor
{
    public const CONSUMER_NAME = 'order-analytics';

    public function __construct(private Connection $connection) {}

    /** Returns false when this consumer already processed the event. */
    public function process(OrderEventEnvelope $event): bool
    {
        return $this->connection->transactional(function () use ($event): bool {
            $inserted = $this->connection->executeStatement(
                'INSERT INTO processed_kafka_events (event_id, consumer_name, processed_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT DO NOTHING',
                [$event->eventId, self::CONSUMER_NAME],
            );
            if ($inserted === 0) {
                return false;
            }

            $this->connection->executeStatement(
                'INSERT INTO order_event_statistics (event_type, processed_count, updated_at) VALUES (?, 1, CURRENT_TIMESTAMP)
                        ON CONFLICT (event_type) DO UPDATE SET processed_count = order_event_statistics.processed_count + 1, updated_at = CURRENT_TIMESTAMP',
                [$event->eventType],
            );

            return true;
        });
    }
}
