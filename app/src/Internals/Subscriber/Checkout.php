<?php

declare(strict_types=1);

namespace App\Internals\Subscriber;

use App\Internals\Event\FakeEvent;
use App\Internals\Listeners\AuditLogListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class Checkout implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

//    public static function getSubscribedServices(): array
//    {
//        return [];
//    }

    public static function getSubscribedEvents()
    {
        return [
            AuditLogListener::class => 'auditLog',
            FakeEvent::class => 'fakeOrder',
        ];
    }

    public function fakeOrder(FakeEvent $event): void {
        $this->logger->info('Checkout->fakeOrder', ['id' => $event]);
    }
}
