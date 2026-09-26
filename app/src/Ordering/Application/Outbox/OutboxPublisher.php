<?php
declare(strict_types=1);
namespace App\Ordering\Application\Outbox;
use App\Ordering\Application\IntegrationEvent\OrderCreated;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Messenger\MessageBusInterface;
final readonly class OutboxPublisher
{
    public function __construct(private Connection $connection, private MessageBusInterface $eventBus) {}
    public function publish(int $limit): int
    {
        return $this->connection->transactional(function () use ($limit): int {
            $messages = $this->connection->fetchAllAssociative('SELECT id, type, payload FROM outbox_messages WHERE published_at IS NULL ORDER BY occurred_at, id FOR UPDATE SKIP LOCKED LIMIT ?', [$limit], [ParameterType::INTEGER]);
            foreach ($messages as $message) {
                if ($message['type'] !== 'order.created.v1') { throw new \LogicException('Unsupported outbox message type.'); }
                $payload = json_decode($message['payload'], true, 512, JSON_THROW_ON_ERROR);
                $this->eventBus->dispatch(new OrderCreated($payload['eventId'], $payload['orderId'], new \DateTimeImmutable($payload['occurredAt'])));
                $this->connection->executeStatement('UPDATE outbox_messages SET published_at = CURRENT_TIMESTAMP, attempts = attempts + 1 WHERE id = ?', [$message['id']]);
            }
            return count($messages);
        });
    }
}
