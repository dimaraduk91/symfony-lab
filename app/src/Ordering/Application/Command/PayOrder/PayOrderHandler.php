<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command\PayOrder;

use App\Ordering\Domain\Repository\OrderRepository;

final readonly class PayOrderHandler
{
    public function __construct(private OrderRepository $repository)
    {
    }

    public function handle(string $id): void
    {
        $order = $this->repository->get($id);

        $order->pay();
    }
}
