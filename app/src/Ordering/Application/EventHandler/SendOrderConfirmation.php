<?php

declare(strict_types=1);

namespace App\Ordering\Application\EventHandler;

use App\Ordering\Application\IntegrationEvent\OrderCreated;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
//#[AsMessage('async')]
final readonly class SendOrderConfirmation
{
    public function __construct(
        private LoggerInterface $logger,
        private Connection $connection
    )
    {
    }

    public function __invoke(OrderCreated $event): void
    {
        $processed = $this->connection->executeStatement(
            'INSERT INTO processed_messages (consumer_name, message_key, processed_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT (consumer_name, message_key) DO NOTHING',
            [self::class, $event->eventId],
        );
        if ($processed === 0) {
            $this->logger->info('Skipped duplicate order confirmation.', ['orderId' => $event->orderId]);
            return;
        }
        // A real email provider should receive this stable business key too.
        $this->logger->info('SendOrderConfirmation', ['orderId' => $event->orderId]);
    }
}
