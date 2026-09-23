<?php

declare(strict_types=1);

namespace App\Ordering\Application\EventHandler;

use App\Ordering\Domain\Event\OrderCreated;
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
        $this->logger->info('Update order statistics with created event for order', ['orderId' => $event->order->getId()]);
        dump('gg');
    }

}
