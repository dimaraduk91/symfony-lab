<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $items;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,
        #[ORM\Column(type: 'string', length: 255)]
        private readonly string $title,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
        $this->items = new ArrayCollection();
    }

    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
