<?php

declare(strict_types=1);

namespace App\Ordering\Infrastracture\Persistance\Doctrine;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;

final class DoctrineOrderRepository implements OrderRepository
{
    private EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $this->entityManager->getRepository(Order::class);
    }

    public function create(Order $order): void
    {
        $this->entityManager->persist($order);
    }

    public function get(string $id): Order
    {
        $order = $this->repository->find($id);
        if (!$order instanceof Order) {
            throw new EntityNotFoundException(sprintf('Order with id "%s" does not exist.', $id));
        }

        return $order;
    }
}
