<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\ValueObjects;

enum Coin: int
{
    case NICKEL = 5;
    case DIME = 10;
    case QUARTER = 25;
    case DOLLAR = 100;

    public function money(): Money
    {
        return new Money($this->value);
    }

    public function label(): string
    {
        return match ($this) {
            self::NICKEL => 'NICKEL',
            self::DIME => 'DIME',
            self::QUARTER => 'QUARTER',
            self::DOLLAR => 'DOLLAR',
        };
    }
}
