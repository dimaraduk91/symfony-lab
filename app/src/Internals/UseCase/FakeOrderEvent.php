<?php

declare(strict_types=1);

namespace App\Internals\UseCase;

use Symfony\Contracts\EventDispatcher\Event;

final class FakeOrderEvent extends Event
{
    public function __construct(public string $id = 'abc')
    {
    }
}
