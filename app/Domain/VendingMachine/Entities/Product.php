<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Entities;

use App\Domain\VendingMachine\ValueObjects\Money;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;
use LogicException;

final class Product
{
    public function __construct(
        public readonly ProductSelector $selector,
        private int $quantity,
    ) {}

    public function price(): Money
    {
        return $this->selector->price();
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    public function dispense(): void
    {
        if (! $this->isInStock()) {
            throw new LogicException('Cannot dispense an out-of-stock product.');
        }

        $this->quantity--;
    }

    public function restock(int $quantity): void
    {
        $this->quantity = $quantity;
    }
}
