<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

final class RequestContext
{
    private ?string $requestId = null;

    public function setRequestId(?string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function clear(): void
    {
        $this->requestId = null;
    }
}
