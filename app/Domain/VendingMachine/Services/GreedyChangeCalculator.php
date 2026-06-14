<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Services;

use App\Domain\VendingMachine\Contracts\ChangeCalculatorInterface;
use App\Domain\VendingMachine\Exceptions\InsufficientChangeException;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;

final class GreedyChangeCalculator implements ChangeCalculatorInterface
{
    public function calculate(Money $amount, CoinCollection $available): CoinCollection
    {
        if ($amount->cents === 0) {
            return CoinCollection::empty();
        }

        $remaining = $amount->cents;
        $change = CoinCollection::empty();

        // Largest denomination first: DOLLAR -> QUARTER -> DIME -> NICKEL
        $coins = array_reverse(Coin::cases());

        foreach ($coins as $coin) {
            if ($remaining <= 0) {
                break;
            }

            $qty = min(
                intdiv($remaining, $coin->value),
                $available->quantityOf($coin),
            );

            if ($qty > 0) {
                $change = $change->add($coin, $qty);
                $remaining -= $coin->value * $qty;
            }
        }

        if ($remaining > 0) {
            throw new InsufficientChangeException(new Money($remaining));
        }

        return $change;
    }
}
