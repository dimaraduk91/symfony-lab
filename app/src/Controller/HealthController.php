<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
//    #[Route('/health', name: 'app_health', methods: ['GET'])]
//    public function __invoke(): JsonResponse
//    {
//        return new JsonResponse([ 'status' => 'Dima ok', ]);
//    }
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    #[Route('/ready', name: 'app_ready', methods: ['GET'])]
    public function ready(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');

            return new JsonResponse([
                'status' => 'ready',
                'database' => 'ok',
            ]);
        } catch (\Throwable) {
            return new JsonResponse([
                'status' => 'not_ready',
                'database' => 'unavailable',
            ], 503);
        }
    }
}
