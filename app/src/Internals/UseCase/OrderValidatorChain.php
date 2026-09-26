<?php

declare(strict_types=1);

namespace App\Internals\UseCase;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class OrderValidatorChain
{
    public function __construct(
        #[AutowireIterator(OrderValidator::class)]
//        #[AutowireLocator([PriceValidator::class, SellerValidator::class])]
        private iterable $validators,
        private LoggerInterface $logger
    ) {
    }

    public function validate(FakeOrderEvent $order): void
    {
        $this->logger->info('--==start OrderValidatorChain==--');
        foreach ($this->validators as $validator) {
            $validator->validate($order);
        }
        $this->logger->info('--==end OrderValidatorChain==--');
    }
}
