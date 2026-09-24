<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\Model;

use App\Ordering\Domain\Model\Order;
use App\Shared\Domain\Money;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testAddingItemsUpdatesMonetaryTotal(): void
    {
        $order = new Order('order-id', 'customer-id', new Money(0, 'EUR'), new ArrayCollection());

        $order->addItem('product-1', 2, new Money(1_999, 'EUR'));
        $order->addItem('product-2', 1, new Money(500, 'EUR'));

        self::assertSame(4_498, $order->getTotal()->getTotalAmount());
        self::assertSame('EUR', $order->getTotal()->getCurrency());
    }

    public function testRejectsAnItemInAnotherCurrency(): void
    {
        $order = new Order('order-id', 'customer-id', new Money(0, 'EUR'), new ArrayCollection());

        $this->expectException(\DomainException::class);

        $order->addItem('product-1', 1, new Money(1_999, 'USD'));
    }
}
