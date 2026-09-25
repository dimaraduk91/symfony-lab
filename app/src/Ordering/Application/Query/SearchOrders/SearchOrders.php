<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query\SearchOrders;

use App\Ordering\Domain\Model\OrderStatus;

final readonly class SearchOrders
{
    public function __construct(
        public ?OrderStatus $status,
        public ?string $customerEmail,
        public ?string $sellerId,
        public ?\DateTimeImmutable $from,
        public ?\DateTimeImmutable $toExclusive,
        public int $limit,
    ) {
    }
}
