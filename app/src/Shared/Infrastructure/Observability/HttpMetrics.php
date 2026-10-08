<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;

final class HttpMetrics
{
    private ?Counter $requests = null;
    private ?Histogram $duration = null;

    public function __construct(private readonly CollectorRegistry $registry)
    {
    }

    public function observe(string $method, string $route, int $status, float $durationSeconds): void
    {
        $this->requests ??= $this->registry->getOrRegisterCounter(
            '',
            'http_requests_total',
            'Total number of HTTP requests.',
            ['method', 'route', 'status'],
        );
        $this->duration ??= $this->registry->getOrRegisterHistogram(
            '',
            'http_request_duration_seconds',
            'HTTP request duration in seconds.',
            ['method', 'route'],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0],
        );

        $this->requests->inc([$method, $route, (string) $status]);
        $this->duration->observe($durationSeconds, [$method, $route]);
    }
}
