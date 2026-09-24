<?php

declare(strict_types=1);

namespace App\Ordering\Presentation\Http\Controller;

use App\Ordering\Application\Command\CreateOrder\CreateOrder;
use App\Ordering\Application\Command\CreateOrder\CreateOrderHandler;
use App\Ordering\Domain\Model\OrderItem;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateOrderController
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    #[Route('/api/orders', name: "create_order", methods: ['POST'])]
    public function handle(#[MapRequestPayload] CreateOrder $order, CreateOrderHandler $handler): JsonResponse
    {
        /**
         * // send email
         */

        try {
            $created = $handler->handle($order);
            $this->logger->info('Order created', ['order' => $created->getId()]);

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            return new JsonResponse(['error' => $exception->getMessage()], 400);
        }

        return new JsonResponse([
            'id' => $created->getId(),
            'customerId' => $created->getCustomerId(),
            'currency' => $created->getTotal()->getCurrency(),
            'status' => $created->getStatus(),
//            'items' => $created->getItems()->toArray(),
            'items' => array_map(
                static fn (OrderItem $item): array => [
                    'productId' => $item->getProductId(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice()->getTotalAmount(),
                ],
                $created->getItems()->toArray(),
            ),
            'amount' => $created->getTotal()->getTotalAmount(),
            'createdAt' => $created->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $created->getUpdatedAt()
        ]);
    }
}
