<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command\CreateOrder;

use App\Shared\Domain\Currency;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrder
{
    /**
     * @param list<CreateOrderItem> $items
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public string $customerId,
        public Currency $currency,
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Type(CreateOrderItem::class)])]
        #[Assert\Valid]
        public array $items,
    ) {
    }
}
