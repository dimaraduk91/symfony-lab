<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use Monolog\LogRecord;
use OpenTelemetry\API\Trace\Span;

final readonly class CorrelationLogProcessor
{
    public function __construct(private RequestContext $requestContext)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        if (($requestId = $this->requestContext->requestId()) !== null) {
            $extra['request_id'] = $requestId;
        }

        $spanContext = Span::getCurrent()->getContext();
        if ($spanContext->isValid()) {
            $extra['trace_id'] = $spanContext->getTraceId();
            $extra['span_id'] = $spanContext->getSpanId();
        }

        return $record->with(extra: $extra);
    }
}
