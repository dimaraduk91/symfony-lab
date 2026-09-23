<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query\GetOrder;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;

final readonly class GetOrderHandler
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    public function handle(GetOrder $getOrder): Order {
        return $this->orderRepository->get($getOrder->id);
    }
}
