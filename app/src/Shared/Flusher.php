<?php

declare(strict_types=1);

namespace App\Shared;

interface Flusher
{
    public function flush(): void;
}
