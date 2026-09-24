<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customers')]
class Customer
{
    /** @var Collection<int, Order> */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer')]
    private Collection $orders;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,
        #[ORM\Column(type: 'string', length: 255, unique: true)]
        private readonly string $email,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
        $this->orders = new ArrayCollection();
    }

    public function getId(): string { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
