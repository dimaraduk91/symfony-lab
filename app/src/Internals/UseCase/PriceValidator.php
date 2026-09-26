<?php

declare(strict_types=1);

namespace App\Internals\UseCase;


use Psr\Log\LoggerInterface;

final class PriceValidator implements OrderValidator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function validate(FakeOrderEvent $order): void
    {
        $this->logger->info('--==PriceValidator==--', ['order' => $order->id]);
    }
}
