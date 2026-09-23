<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

use App\Ordering\Domain\Model\Order;

final readonly class OrderCreated
{
    public function __construct(public Order $order)
    {
    }
}
