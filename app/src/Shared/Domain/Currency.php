<?php

declare(strict_types=1);

namespace App\Shared\Domain;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
}
