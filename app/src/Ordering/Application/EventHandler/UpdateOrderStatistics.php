<?php

declare(strict_types=1);

namespace App\Ordering\Application\EventHandler;

use App\Ordering\Application\IntegrationEvent\OrderCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class UpdateOrderStatistics
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(OrderCreated $event): void
    {
        // here should be email sender.
        $this->logger->info('Update order statistics.', ['orderId' => $event->orderId]);
    }

}
