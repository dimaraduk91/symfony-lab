<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Shared\Domain\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Order $order,
        #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'items')]
        #[ORM\JoinColumn(nullable: false)]
        private Product $product,
        #[ORM\ManyToOne(targetEntity: Seller::class, inversedBy: 'items')]
        #[ORM\JoinColumn(nullable: false)]
        private Seller $seller,
        #[ORM\Column(type: 'integer')]
        private int $quantity,
        Money $price,
    )
    {
        if ($this->quantity <= 0 || $price->getTotalAmount() <= 0) {
            throw new \LogicException('Quantity and price must be greater than 0');
        }

        if ($price->getCurrency() !== $this->order->getTotal()->getCurrency()) {
            throw new \DomainException('Item price currency must match order currency.');
        }

        $this->price = $price->getTotalAmount();
    }

    #[ORM\Column(type: 'integer')]
    private int $price;


    public function getId(): int
    {
        return $this->id;
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getPrice(): Money
    {
        return new Money($this->price, $this->order->getTotal()->getCurrency());
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getProductId(): string
    {
        return $this->product->getId();
    }

    public function getSellerId(): string { return $this->seller->getId(); }

    public function total(): Money
    {
        return $this->getPrice()->multiply($this->quantity);
    }
}
