<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class OrderClass
{
    #[Route('/api/orders', name: "create_order", methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $request->getBaseUrl();
        // validation
        // SQL
        // business logic
        // logging
        // sending notification
        // response

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
