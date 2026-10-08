<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Messenger;

use App\Shared\Infrastructure\Observability\RequestContext;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final readonly class ObservabilityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TracerInterface $tracer,
        private RequestContext $requestContext,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $isConsumer = $envelope->last(ReceivedStamp::class) instanceof ReceivedStamp;
        $incoming = $envelope->last(TraceContextStamp::class);
        $parent = $incoming instanceof TraceContextStamp
            ? TraceContextPropagator::getInstance()->extract($incoming->carrier, context: Context::getRoot())
            : Context::getCurrent();
        $messageName = (new \ReflectionClass($envelope->getMessage()))->getShortName();
        $operation = $isConsumer ? 'process' : 'publish';
        $previousRequestId = $this->requestContext->requestId();

        if ($isConsumer || ($incoming instanceof TraceContextStamp && $incoming->requestId !== null)) {
            $this->requestContext->clear();
            $this->requestContext->setRequestId($incoming instanceof TraceContextStamp ? $incoming->requestId : null);
        }

        $span = $this->tracer->spanBuilder(sprintf('%s %s', $messageName, $operation))
            ->setParent($parent)
            ->setSpanKind($isConsumer ? SpanKind::KIND_CONSUMER : SpanKind::KIND_PRODUCER)
            ->setAttributes([
                'messaging.system' => 'rabbitmq',
                'messaging.operation.name' => $operation,
                'messaging.message.type' => $envelope->getMessage()::class,
            ])
            ->startSpan();
        $scope = $span->activate();

        if (!$isConsumer) {
            $carrier = [];
            TraceContextPropagator::getInstance()->inject($carrier);
            $envelope = $envelope
                ->withoutAll(TraceContextStamp::class)
                ->with(new TraceContextStamp(
                    $carrier,
                    $incoming instanceof TraceContextStamp ? $incoming->requestId : $this->requestContext->requestId(),
                ));
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());

            throw $exception;
        } finally {
            $scope->detach();
            $span->end();
            $this->requestContext->setRequestId($previousRequestId);
        }
    }
}
