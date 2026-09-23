<?php

declare(strict_types=1);

namespace App\src\Ordering\Domain\Model\Order;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'int')]
    private int $id = 0;

    public function getId(): int
    {
        return $this->id;
    }
}
