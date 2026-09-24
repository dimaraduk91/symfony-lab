<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

final class OrderCannotBePaid extends \DomainException
{
    public function __construct() {
        parent::__construct("Order cannot be paid", 400);
    }
}
