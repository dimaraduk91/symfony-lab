<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

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
        #[ORM\Column(type: 'string', length: 36)]
        private string $productId,
        #[ORM\Column(type: 'integer')]
        private int    $quantity = 0,
        #[ORM\Column(type: 'integer')]
        private int    $price = 0,
    )
    {
    }


    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

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

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }
}
