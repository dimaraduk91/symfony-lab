<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Column(type: 'integer')]
    private int $amount = 0;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string               $id,
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string                 $customerId,
        #[ORM\Column(type: 'string', length: 3)]
        private readonly string $currency,
        #[ORM\OneToMany(
            targetEntity: OrderItem::class,
            mappedBy: 'order',
            cascade: ['persist'],
            orphanRemoval: true,
        )]
        private Collection $items = new ArrayCollection(),
        #[ORM\Column(type: 'datetimetz_immutable')]
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        #[ORM\Column(type: 'datetimetz_immutable')]
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable(),
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->items->contains($item)) {
            return;
        }
        $this->amount += $item->getPrice() * $item->getQuantity();

        $this->items->add($item);
        $item->setOrder($this);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
