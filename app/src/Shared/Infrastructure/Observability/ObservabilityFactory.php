<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use OpenTelemetry\SDK\Trace\TracerProviderFactory;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

final class ObservabilityFactory
{
    public static function createMetricsRegistry(string $host, int $port): CollectorRegistry
    {
        return new CollectorRegistry(new Redis([
            'host' => $host,
            'port' => $port,
            'timeout' => 0.5,
            'read_timeout' => 0.5,
            'persistent_connections' => false,
        ]));
    }

    public static function createTracerProvider(): TracerProviderInterface
    {
        return (new TracerProviderFactory())->create();
    }
}
