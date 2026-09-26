<?php

declare(strict_types=1);

namespace App\Internals\UseCase;


use Psr\Log\LoggerInterface;

final class StockValidator implements OrderValidator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function validate(FakeOrder $order): void
    {
        $this->logger->info('--==StockValidator==--');
    }
}
