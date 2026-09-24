<?php

declare(strict_types=1);

namespace App\Ordering\Presentation\Http\Controller;

use App\Ordering\Application\Query\GetOrder\GetOrder;
use App\Ordering\Application\Query\GetOrder\GetOrderHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetOrderAction
{
    public function __construct(private GetOrderHandler $handler)
    {
    }

    #[Route(
        '/api/orders/{id}',
        name: 'read_order',
        requirements: ['id' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function get(string $id): JsonResponse
    {
        $order = $this->handler->handle(new GetOrder($id));

        return new JsonResponse([
            "id" => $order->getId(),
            "status" => $order->getStatus(),
            "total" => $order->getTotal()->getTotalAmount(),
            "currency" => $order->getTotal()->getCurrency(),
        ]);
    }
}
