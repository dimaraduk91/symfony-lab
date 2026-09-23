<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Repository;

use App\Ordering\Domain\Model\Order;

interface OrderRepository {
    public function create(Order $order): void;
    public function get(string $id): Order;
}
