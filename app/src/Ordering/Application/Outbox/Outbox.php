<?php

declare(strict_types=1);

namespace App\Ordering\Application\Outbox;

use App\Ordering\Application\IntegrationEvent\OrderCreated;

interface Outbox
{
    public function add(OrderCreated $event): void;
}
