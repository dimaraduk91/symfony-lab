<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

final readonly class TraceContextStamp implements StampInterface
{
    /** @param array<string, string> $carrier */
    public function __construct(
        public array $carrier,
        public ?string $requestId = null,
    ) {
    }
}
