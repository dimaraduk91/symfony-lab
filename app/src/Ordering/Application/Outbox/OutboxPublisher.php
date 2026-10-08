<?php
declare(strict_types=1);
namespace App\Ordering\Application\Outbox;
use App\Ordering\Application\IntegrationEvent\OrderCreated;
use App\Shared\Infrastructure\Observability\Messenger\TraceContextStamp;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
final readonly class OutboxPublisher
{
    public function __construct(private Connection $connection, private MessageBusInterface $eventBus) {}
    public function publish(int $limit): int
    {
        return $this->connection->transactional(function () use ($limit): int {
            $messages = $this->connection->fetchAllAssociative('SELECT id, type, payload, occurred_at, request_id, traceparent, tracestate FROM outbox_messages WHERE published_at IS NULL ORDER BY occurred_at, id FOR UPDATE SKIP LOCKED LIMIT ?', [$limit], [ParameterType::INTEGER]);
            foreach ($messages as $message) {
                if ($message['type'] !== 'order.created.v1') { throw new \LogicException('Unsupported outbox message type.'); }
                $payload = json_decode($message['payload'], true, 512, JSON_THROW_ON_ERROR);
                $carrier = array_filter([
                    'traceparent' => $message['traceparent'],
                    'tracestate' => $message['tracestate'],
                ], static fn (mixed $value): bool => is_string($value) && $value !== '');
                $this->eventBus->dispatch(new Envelope(
                    new OrderCreated(
                        $payload['eventId'] ?? $message['id'],
                        $payload['orderId'],
                        new \DateTimeImmutable($payload['occurredAt'] ?? $message['occurred_at']),
                    ),
                    [new TraceContextStamp($carrier, $message['request_id'])],
                ));
                $this->connection->executeStatement('UPDATE outbox_messages SET published_at = CURRENT_TIMESTAMP, attempts = attempts + 1 WHERE id = ?', [$message['id']]);
            }
            return count($messages);
        });
    }
}
