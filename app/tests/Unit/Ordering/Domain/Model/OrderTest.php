<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\Model;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Model\Customer;
use App\Ordering\Domain\Model\Product;
use App\Ordering\Domain\Model\Seller;
use App\Shared\Domain\Money;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testAddingItemsUpdatesMonetaryTotal(): void
    {
        $order = new Order('order-id', new Customer('customer-id', 'customer@example.test'), new Money(0, 'EUR'), new ArrayCollection());

        $seller = new Seller('seller-id', 'Seller');
        $order->addItem(new Product('product-1', 'Product 1'), $seller, 2, new Money(1_999, 'EUR'));
        $order->addItem(new Product('product-2', 'Product 2'), $seller, 1, new Money(500, 'EUR'));

        self::assertSame(4_498, $order->getTotal()->getTotalAmount());
        self::assertSame('EUR', $order->getTotal()->getCurrency());
    }

    public function testRejectsAnItemInAnotherCurrency(): void
    {
        $order = new Order('order-id', new Customer('customer-id', 'customer@example.test'), new Money(0, 'EUR'), new ArrayCollection());

        $this->expectException(\DomainException::class);

        $order->addItem(new Product('product-1', 'Product 1'), new Seller('seller-id', 'Seller'), 1, new Money(1_999, 'USD'));
    }
}
