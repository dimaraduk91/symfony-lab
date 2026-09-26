<?php

declare(strict_types=1);

namespace App\Internals\Listeners;

use App\Internals\Event\FakeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class SendAnalyticsListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(FakeEvent $event): void
    {
        $this->logger->info('execute SendAnalyticsListener', ['eventId' => $event->id]);
    }
}
