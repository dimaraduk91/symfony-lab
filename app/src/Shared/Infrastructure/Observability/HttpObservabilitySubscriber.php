<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ScopeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final readonly class HttpObservabilitySubscriber implements EventSubscriberInterface
{
    private const START_TIME = '_observability_start_time';
    private const SPAN = '_observability_span';
    private const SCOPE = '_observability_scope';
    private const REQUEST_ID = '_request_id';
    private const UNTRACED_PATHS = ['/health', '/metrics'];

    public function __construct(
        private RequestContext $requestContext,
        private HttpMetrics $metrics,
        private TracerInterface $tracer,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 1024],
            KernelEvents::EXCEPTION => ['onException', -1024],
            KernelEvents::RESPONSE => ['onResponse', -1024],
            KernelEvents::FINISH_REQUEST => ['onFinishRequest', -1024],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $this->requestContext->clear();

        $requestId = $this->resolveRequestId($request);
        $this->requestContext->setRequestId($requestId);
        $request->attributes->set(self::REQUEST_ID, $requestId);
        $request->attributes->set(self::START_TIME, hrtime(true));

        if (in_array($request->getPathInfo(), self::UNTRACED_PATHS, true)) {
            return;
        }

        $parent = TraceContextPropagator::getInstance()->extract($request->headers->all(), context: Context::getRoot());
        $span = $this->tracer->spanBuilder(sprintf('%s HTTP request', $request->getMethod()))
            ->setParent($parent)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttributes([
                'http.request.method' => $request->getMethod(),
                'url.path' => $request->getPathInfo(),
                'request.id' => $requestId,
            ])
            ->startSpan();

        $request->attributes->set(self::SPAN, $span);
        $request->attributes->set(self::SCOPE, $span->activate());
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $span = $event->getRequest()->attributes->get(self::SPAN);
        if ($span instanceof SpanInterface) {
            $span->recordException($event->getThrowable());
            $span->setStatus(StatusCode::STATUS_ERROR, $event->getThrowable()->getMessage());
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = $request->attributes->getString(self::REQUEST_ID);
        if ($requestId !== '') {
            $response->headers->set('X-Request-ID', $requestId);
        }

        $route = $request->attributes->getString('_route', 'unmatched');
        if ($route !== 'app_metrics') {
            $startedAt = $request->attributes->get(self::START_TIME);
            if (is_int($startedAt)) {
                try {
                    $this->metrics->observe(
                        $request->getMethod(),
                        $route,
                        $response->getStatusCode(),
                        (hrtime(true) - $startedAt) / 1_000_000_000,
                    );
                } catch (\Throwable $exception) {
                    $this->logger->warning('HTTP metrics update failed.', ['exception' => $exception]);
                }
            }
        }

        $span = $request->attributes->get(self::SPAN);
        if ($span instanceof SpanInterface) {
            $span->updateName(sprintf('%s %s', $request->getMethod(), $route));
            $span->setAttributes([
                'http.route' => $route,
                'http.response.status_code' => $response->getStatusCode(),
            ]);
            if ($response->getStatusCode() >= 500) {
                $span->setStatus(StatusCode::STATUS_ERROR);
            }
        }

        $this->finish($request);
    }

    public function onFinishRequest(FinishRequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->finish($event->getRequest());
        }
    }

    private function finish(Request $request): void
    {
        $scope = $request->attributes->get(self::SCOPE);
        if ($scope instanceof ScopeInterface) {
            $scope->detach();
            $request->attributes->remove(self::SCOPE);
        }

        $span = $request->attributes->get(self::SPAN);
        if ($span instanceof SpanInterface) {
            $span->end();
            $request->attributes->remove(self::SPAN);
        }

        $this->requestContext->clear();
    }

    private function resolveRequestId(Request $request): string
    {
        $candidate = trim((string) $request->headers->get('X-Request-ID'));

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/D', $candidate) === 1
            ? $candidate
            : Uuid::v7()->toRfc4122();
    }
}
