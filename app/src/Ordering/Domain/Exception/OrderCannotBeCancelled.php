<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

final class OrderCannotBeCancelled extends \DomainException
{
    public function __construct(string $message = "")
    {
        parent::__construct('Order cannot be cancelled', 400);
    }
}
