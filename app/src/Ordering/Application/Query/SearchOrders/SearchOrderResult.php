<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query\SearchOrders;

final readonly class SearchOrderResult
{
    public function __construct(
        public string $id,
        public string $status,
        public string $customerId,
        public string $customerEmail,
        public int $amount,
        public string $currency,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
