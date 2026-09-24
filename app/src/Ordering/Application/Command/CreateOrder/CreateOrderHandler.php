<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command\CreateOrder;

use App\Ordering\Domain\Event\OrderCreated;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Model\Customer;
use App\Ordering\Domain\Model\Product;
use App\Ordering\Domain\Model\Seller;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Shared\Domain\Money;
use App\Shared\Flusher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\UuidV7;

final readonly class CreateOrderHandler
{
    public function __construct(private OrderRepository $repository, private MessageBusInterface $eventBus, private Flusher $flush, private EntityManagerInterface $entityManager)
    {
    }

    public function handle(CreateOrder $order): Order
    {
        $created = new Order(
            (new UuidV7())->toString(),
            $this->entityManager->getReference(Customer::class, $order->customerId),
            new Money(0, $order->currency->value),
            new ArrayCollection()
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

        $this->flush->flush();

        $this->eventBus->dispatch(new OrderCreated($created->getId()));

        return $created;
    }
}
