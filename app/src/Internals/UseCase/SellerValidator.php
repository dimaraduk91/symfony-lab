<?php

declare(strict_types=1);

namespace App\Internals\UseCase;


use Psr\Log\LoggerInterface;

final class SellerValidator implements OrderValidator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function validate(FakeOrderEvent $order): void
    {
        $this->logger->info('--==SellerValidator==--', ['order' => $order->id]);
    }
}
