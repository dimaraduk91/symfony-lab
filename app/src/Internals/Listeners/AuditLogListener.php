<?php

declare(strict_types=1);

namespace App\Internals\Listeners;

use App\Internals\Event\FakeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class AuditLogListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(FakeEvent $event): void
    {
        $this->logger->info('execute AuditLogListener', ['eventId' => $event->id]);
    }

}
