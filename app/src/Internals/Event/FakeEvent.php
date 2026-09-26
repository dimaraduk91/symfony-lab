<?php

declare(strict_types=1);

namespace App\Internals\Event;

final class FakeEvent
{
    public function __construct(public string $id)
    {
    }
}
