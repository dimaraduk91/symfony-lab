<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

final class OrderCannotBeCompleted extends \DomainException
{
    public function __construct(string $message = 'Order cannot be completed')
    {
        parent::__construct($message, 400);
    }
}
