<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Contracts;

use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;

interface ChangeCalculatorInterface
{
    public function calculate(Money $amount, CoinCollection $available): CoinCollection;
}
