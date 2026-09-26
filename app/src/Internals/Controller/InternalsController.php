<?php

declare(strict_types=1);

namespace App\Internals\Controller;

use App\Internals\Event\FakeEvent;
use App\Internals\UseCase\FakeOrderEvent;
use App\Internals\UseCase\OrderValidatorChain;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class InternalsController
{
    public function __construct(
        private LoggerInterface $logger,
        private EventDispatcherInterface  $eventDispatcher,
    ) {
    }

    #[Route('/internals/lifecycle', name: 'app_internals_lifecycle', methods: ['GET'])]
    public function lifecycle(): JsonResponse
    {

        $this->eventDispatcher->dispatch(new FakeEvent('123'));

        $this->logger->info('--==controller executed==--');

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/internals/exception', name: 'app_internals_exception', methods: ['GET'])]
    public function exception(): never
    {
        $this->logger->info('controller executed');

        throw new \RuntimeException('Intentional exception from /internals/exception.');
    }

    #[Route('/internals/validator', name: 'app_internals_fake-validator', methods: ['GET'])]
    public function fakeValidator(OrderValidatorChain $validatorChain): JsonResponse
    {
        $this->logger->info('--==controller executed==--');

        $validatorChain->validate(new FakeOrderEvent());

        return new JsonResponse(['status' => 'ok']);
    }
}
