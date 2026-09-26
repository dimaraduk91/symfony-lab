<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\Exception\OrderCannotBeCancelled;
use App\Ordering\Domain\Exception\OrderCannotBeCompleted;
use App\Ordering\Domain\Exception\OrderCannotBePaid;
use App\Shared\Domain\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\Index(name: 'IDX_ORDERS_STATUS_CREATED_AT', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'IDX_ORDERS_CREATED_AT', columns: ['created_at'])]
class Order
{
    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Embedded(class: Money::class, columnPrefix: false)]
    private Money $total;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string               $id,
        #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'orders')]
        #[ORM\JoinColumn(nullable: false)]
        private readonly Customer $customer,
        Money $total,
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
        #[ORM\Column(name: 'idempotency_key', type: 'string', length: 128, unique: true, nullable: true)]
        private readonly ?string $idempotencyKey = null,
    )
    {
        $this->total = $total;
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
        return $this->customer->getId();
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Product $product, Seller $seller, int $quantity, Money $price): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new \DomainException('Order cannot be modified.');
        }

        $item = new OrderItem($this, $product, $seller, $quantity, $price);
        $this->total = $this->total->add($item->total());
        $this->items->add($item);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function pay(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new OrderCannotBePaid();
        }
        $this->status = OrderStatus::Paid;
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new OrderCannotBeCancelled();
        }
        $this->status = OrderStatus::Cancelled;
    }

    public function complete(): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new OrderCannotBeCompleted();
        }
        $this->status = OrderStatus::Completed;
    }
}
