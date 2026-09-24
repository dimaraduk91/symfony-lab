<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

final readonly class OrderCreated
{
    public function __construct(public string $order)
    {
    }
}
