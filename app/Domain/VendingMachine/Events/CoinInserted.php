<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Events;

use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\Money;

final readonly class CoinInserted
{
    public function __construct(
        public Coin $coin,
        public Money $totalInserted,
    ) {}
}
