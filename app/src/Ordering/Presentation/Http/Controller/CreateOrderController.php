<?php

declare(strict_types=1);

namespace App\Ordering\Presentation\Http\Controller;

use App\Dto\Order\CreateOrderRequest;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateOrderHandler
{
    #[Route('/api/orders', name: "create_order", methods: ['POST'])]
    public function handle(#[MapRequestPayload] CreateOrderRequest $order, Create $handler): void
    {
        $handler->handle($order);
    }
}
