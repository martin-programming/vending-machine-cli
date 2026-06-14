<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Events;

use App\Domain\VendingMachine\ValueObjects\CoinCollection;

final readonly class MachineRestocked
{
    public function __construct(
        public CoinCollection $coinFloat,
        public int $productCount,
    ) {}
}
