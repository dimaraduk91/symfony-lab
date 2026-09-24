<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain;

use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testAddsMoneyWithTheSameCurrency(): void
    {
        $result = (new Money(1_999, 'EUR'))->add(new Money(500, 'EUR'));

        self::assertSame(2_499, $result->getTotalAmount());
        self::assertSame('EUR', $result->getCurrency());
    }

    public function testRejectsAddingDifferentCurrencies(): void
    {
        $this->expectException(\DomainException::class);

        (new Money(1_999, 'EUR'))->add(new Money(1_999, 'USD'));
    }

    public function testMultipliesAmount(): void
    {
        $result = (new Money(1_999, 'EUR'))->multiply(2);

        self::assertSame(3_998, $result->getTotalAmount());
        self::assertSame('EUR', $result->getCurrency());
    }

    public function testRejectsUnsupportedCurrency(): void
    {
        $this->expectException(\DomainException::class);

        new Money(1_999, 'BYN');
    }
}
