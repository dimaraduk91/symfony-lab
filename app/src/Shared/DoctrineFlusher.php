<?php

declare(strict_types=1);

namespace App\Shared;

use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFlusher implements Flusher
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
