<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\ValueObjects;

enum ProductSelector: string
{
    case WATER = 'WATER';
    case JUICE = 'JUICE';
    case SODA = 'SODA';

    public function price(): Money
    {
        return match ($this) {
            self::WATER => new Money(65),
            self::JUICE => new Money(100),
            self::SODA => new Money(150),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::WATER => 'Water',
            self::JUICE => 'Juice',
            self::SODA => 'Soda',
        };
    }
}
