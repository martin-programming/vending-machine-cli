<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Events;

use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;

final readonly class CoinsReturned
{
    public function __construct(
        public CoinCollection $coins,
        public Money $total,
    ) {}
}
