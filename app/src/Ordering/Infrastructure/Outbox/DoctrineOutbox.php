<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Outbox;

use App\Ordering\Application\IntegrationEvent\OrderCreated;
use App\Ordering\Application\Outbox\Outbox;
use App\Shared\Infrastructure\Observability\RequestContext;
use Doctrine\DBAL\Connection;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

final readonly class DoctrineOutbox implements Outbox
{
    public function __construct(
        private Connection $connection,
        private RequestContext $requestContext,
    ) {
    }

    public function add(OrderCreated $event): void
    {
        $traceCarrier = [];
        TraceContextPropagator::getInstance()->inject($traceCarrier);

        $this->connection->insert('outbox_messages', [
            'id' => $event->eventId,
            'type' => $event::type,
            'payload' => json_encode([
                'eventId' => $event->eventId,
                'orderId' => $event->orderId,
                'occurredAt' => $event->occurredAt->format(DATE_ATOM),
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:sP'),
            'request_id' => $this->requestContext->requestId(),
            'traceparent' => $traceCarrier['traceparent'] ?? null,
            'tracestate' => $traceCarrier['tracestate'] ?? null,
        ]);
    }
}
