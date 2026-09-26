<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command\CreateOrder;

use App\Ordering\Application\IntegrationEvent\OrderCreated;
use App\Ordering\Application\Outbox\Outbox;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Model\Customer;
use App\Ordering\Domain\Model\Product;
use App\Ordering\Domain\Model\Seller;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Shared\Domain\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\UuidV7;

final readonly class CreateOrderHandler
{
    public function __construct(private OrderRepository $repository, private EntityManagerInterface $entityManager, private Connection $connection, private Outbox $outbox)
    {
    }

    public function handle(CreateOrder $order, string $idempotencyKey): Order
    {
        return $this->entityManager->wrapInTransaction(function () use ($order, $idempotencyKey): Order {

        /**
         * 1. то координация конкурентных HTTP retries с одним Idempotency-Key.
         * Без него два одновременных запроса могут оба не найти existing order и почти одновременно попытаться создать его.
         * Финальная защита всё равно — UNIQUE(idempotency_key), но один запрос тогда упадёт с unique violation.
         * Lock заставляет второй запрос дождаться первого, после чего он просто прочитает созданный order и вернёт его.
         * Это не обязательная часть correctness: unique constraint — гарантия.
         * Advisory lock улучшает UX идемпотентного retry, убирая конфликт 500/409 при гонке.
         */
        $this->connection->executeStatement('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [$idempotencyKey]);
        $existing = $this->entityManager->getRepository(Order::class)->findOneBy(['idempotencyKey' => $idempotencyKey]);
        if ($existing instanceof Order) {
            return $existing;
        }
        $created = new Order(
            (new UuidV7())->toString(),
            $this->entityManager->getReference(Customer::class, $order->customerId),
            new Money(0, $order->currency->value),
            new ArrayCollection(),
            idempotencyKey: $idempotencyKey,
        );

        foreach ($order->items as $item) {
            $created->addItem(
                $this->entityManager->getReference(Product::class, $item->productId),
                $this->entityManager->getReference(Seller::class, $item->sellerId),
                $item->quantity,
                new Money($item->price, $order->currency->value),
            );
        }

        $this->repository->create($created);

        $occurredAt = new \DateTimeImmutable();
        $this->outbox->add(new OrderCreated((new UuidV7())->toString(), $created->getId(), $occurredAt));
        $this->entityManager->flush();

        return $created;
        });
    }
}
