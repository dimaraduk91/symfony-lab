<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command\CreateOrder;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderItem
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public string $productId,
        #[Assert\Positive]
        public int $quantity,
        #[Assert\Positive]
        public int $price,
    ) {
    }
}
