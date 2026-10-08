<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Messenger;

use App\Shared\Infrastructure\Observability\Messenger\ObservabilityMiddleware;
use App\Shared\Infrastructure\Observability\Messenger\TraceContextStamp;
use App\Shared\Infrastructure\Observability\RequestContext;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

final class ObservabilityMiddlewareTest extends TestCase
{
    public function testProducerAddsCorrelationStamp(): void
    {
        $requestContext = new RequestContext();
        $requestContext->setRequestId('request-42');
        $middleware = new ObservabilityMiddleware((new TracerProvider())->getTracer('test'), $requestContext);
        $terminal = new class implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                return $envelope;
            }
        };
        $stack = new StackMiddleware($terminal);

        $result = $middleware->handle(new Envelope(new \stdClass()), $stack);
        $stamp = $result->last(TraceContextStamp::class);

        self::assertInstanceOf(TraceContextStamp::class, $stamp);
        self::assertSame('request-42', $stamp->requestId);
        self::assertArrayHasKey('traceparent', $stamp->carrier);
    }
}
