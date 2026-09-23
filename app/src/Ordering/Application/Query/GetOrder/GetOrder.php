<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query\GetOrder;

use Symfony\Component\Validator\Constraints as Assert;

final class GetOrder
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $id,
    ) {
    }
}
