<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\ValueObjects;

final readonly class DispenseResult
{
    public function __construct(
        public ProductSelector $product,
        public CoinCollection $change,
    ) {}
}
