<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use Doctrine\DBAL\Connection;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MetricsController
{
    public function __construct(
        private CollectorRegistry $registry,
        private Connection $connection,
    ) {
    }

    #[Route('/metrics', name: 'app_metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        $pending = $this->connection->fetchAssociative(<<<'SQL'
            SELECT COUNT(*) AS count,
                   COALESCE(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(occurred_at))), 0) AS oldest_age
            FROM outbox_messages
            WHERE published_at IS NULL
            SQL);

        $this->registry->getOrRegisterGauge('', 'outbox_messages_pending', 'Number of unpublished outbox messages.')
            ->set((float) $pending['count']);
        $this->registry->getOrRegisterGauge('', 'outbox_oldest_message_age_seconds', 'Age of the oldest unpublished outbox message in seconds.')
            ->set((float) $pending['oldest_age']);

        $renderer = new RenderTextFormat();

        return new Response(
            $renderer->render($this->registry->getMetricFamilySamples()),
            Response::HTTP_OK,
            ['Content-Type' => RenderTextFormat::MIME_TYPE],
        );
    }
}
