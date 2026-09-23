<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
