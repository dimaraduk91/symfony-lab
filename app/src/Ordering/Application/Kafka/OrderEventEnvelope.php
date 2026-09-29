<?php

declare(strict_types=1);

namespace App\Ordering\Application\Kafka;

final readonly class OrderEventEnvelope
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $eventId,
        public string $eventType,
        public string $aggregateId,
        public \DateTimeImmutable $occurredAt,
        public array $payload = [],
    ) {}

    public function toJson(): string
    {
        return json_encode([
            'eventId' => $this->eventId,
            'eventType' => $this->eventType,
            'aggregateId' => $this->aggregateId,
            'occurredAt' => $this->occurredAt->format(DATE_ATOM),
            'payload' => $this->payload,
        ], JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        foreach (['eventId', 'eventType', 'aggregateId', 'occurredAt', 'payload'] as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException(sprintf('Missing event envelope field "%s".', $field));
            }
        }
        if (!is_array($data['payload'])) {
            throw new \InvalidArgumentException('Event payload must be an object.');
        }

        return new self((string) $data['eventId'], (string) $data['eventType'], (string) $data['aggregateId'], new \DateTimeImmutable((string) $data['occurredAt']), $data['payload']);
    }
}
