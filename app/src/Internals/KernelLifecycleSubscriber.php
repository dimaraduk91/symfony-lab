<?php

declare(strict_types=1);

namespace App\Internals;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class KernelLifecycleSubscriber implements EventSubscriberInterface
{
    private const ROUTE_PREFIX = '/internals/';

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::CONTROLLER => 'onController',
            KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments',
            KernelEvents::RESPONSE => 'onResponse',
            KernelEvents::FINISH_REQUEST => 'onFinishRequest',
            KernelEvents::TERMINATE => 'onTerminate',
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $this->log($event, 'kernel.request updated');
        if ($event->getRequest()->query->has('shortCircuit')) {
            $event->setResponse(
                new JsonResponse(['source' => 'kernel.request'])
            );
        }
    }

    public function onController(ControllerEvent $event): void
    {
        $this->log($event, 'kernel.controller');
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        $this->log($event, 'kernel.controller_arguments');
    }

    public function onResponse(ResponseEvent $event): void
    {
        $this->log($event, 'kernel.response');
    }

    public function onFinishRequest(FinishRequestEvent $event): void
    {
        $this->log($event, 'kernel.finish_request');
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $this->log($event, 'kernel.terminate');
    }

    public function onException(ExceptionEvent $event): void
    {
        $this->log($event, 'kernel.exception', [
            'exception' => $event->getThrowable()::class,
        ]);
    }

    private function log(object $event, string $message, array $context = []): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->getPathInfo(), self::ROUTE_PREFIX)) {
            return;
        }

        $this->logger->info($message, $context);
    }
}
