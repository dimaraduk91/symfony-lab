<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Kafka;

use App\Ordering\Application\Kafka\DomainEventPublisher;
use App\Ordering\Application\Kafka\OrderEventEnvelope;

final class KafkaDomainEventPublisher implements DomainEventPublisher
{
    private readonly \RdKafka\Producer $producer;
    private readonly \RdKafka\ProducerTopic $topic;

    public function __construct(string $brokers)
    {
        $config = new \RdKafka\Conf();
        $config->set('bootstrap.servers', $brokers);
        $config->set('enable.idempotence', 'true');
        $config->set('acks', 'all');
        $this->producer = new \RdKafka\Producer($config);
        $this->topic = $this->producer->newTopic('order.events');
    }

    public function publish(string $eventId, string $eventType, string $aggregateId, \DateTimeImmutable $occurredAt, array $payload = []): void
    {
        $envelope = new OrderEventEnvelope($eventId, $eventType, $aggregateId, $occurredAt, $payload);
        // aggregateId is the key: all records for one Order are deterministically routed to one partition.
        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $envelope->toJson(), $aggregateId);
        $this->producer->poll(0);
        if ($this->producer->flush(10_000) !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new \RuntimeException('Kafka producer did not flush within 10 seconds.');
        }
    }
}
