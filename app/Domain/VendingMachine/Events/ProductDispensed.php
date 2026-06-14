<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Events;

use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;

final readonly class ProductDispensed
{
    public function __construct(
        public ProductSelector $product,
        public Money $paid,
        public CoinCollection $change,
    ) {}
}
