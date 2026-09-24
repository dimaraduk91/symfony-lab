<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Money
{
    public function __construct(
        #[ORM\Column(name: 'amount', type: 'integer')]
        private int $totalAmount,
        #[ORM\Column(name: 'currency', type: 'string', length: 3)]
        private string $currency,
    ) {
        if ($this->totalAmount < 0) {
            throw new \DomainException('Total amount must not be negative.');
        }

        if (!in_array($this->currency, array_column(Currency::cases(), 'value'), true)) {
            throw new \DomainException(sprintf('Unsupported currency "%s".', $this->currency));
        }
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException('Currency is different.');
        }

        return new self(
            $this->totalAmount + $other->totalAmount,
            $this->currency,
        );
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new \DomainException('Multiplier must not be negative.');
        }

        return new self($this->totalAmount * $multiplier, $this->currency);
    }
}
