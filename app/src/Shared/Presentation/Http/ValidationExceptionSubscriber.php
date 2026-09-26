<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ValidationExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $validationException = $this->findValidationException($event->getThrowable());

        if ($validationException === null) {
            return;
        }

        $errors = [];
        foreach ($validationException->getViolations() as $violation) {
            $field = $violation->getPropertyPath() ?: 'payload';
            $errors[$field][] = $violation->getMessage();
        }

        $event->setResponse(new JsonResponse([
            'error' => 'Validation failed.',
            'errors' => $errors,
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }

    private function findValidationException(\Throwable $exception): ?ValidationFailedException
    {
        do {
            if ($exception instanceof ValidationFailedException) {
                return $exception;
            }

            $exception = $exception->getPrevious();
        } while ($exception !== null);

        return null;
    }
}
