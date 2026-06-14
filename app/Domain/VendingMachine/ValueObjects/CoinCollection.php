<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\ValueObjects;

use InvalidArgumentException;

final class CoinCollection
{
    /** @var array<int, int> Coin backing value => quantity */
    private array $coins;

    /** @param array<int, int> $coins */
    private function __construct(array $coins)
    {
        $this->coins = $coins;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function add(Coin $coin, int $quantity = 1): self
    {
        $coins = $this->coins;
        $coins[$coin->value] = ($coins[$coin->value] ?? 0) + $quantity;

        return new self($coins);
    }

    public function merge(self $other): self
    {
        $coins = $this->coins;

        foreach ($other->coins as $value => $qty) {
            $coins[$value] = ($coins[$value] ?? 0) + $qty;
        }

        return new self($coins);
    }

    public function subtract(self $other): self
    {
        $coins = $this->coins;

        foreach ($other->coins as $value => $qty) {
            $newQty = ($coins[$value] ?? 0) - $qty;

            if ($newQty < 0) {
                throw new InvalidArgumentException('Cannot subtract more coins than available.');
            }

            $coins[$value] = $newQty;
        }

        return new self($coins);
    }

    public function quantityOf(Coin $coin): int
    {
        return $this->coins[$coin->value] ?? 0;
    }

    public function totalMoney(): Money
    {
        $total = 0;

        foreach ($this->coins as $value => $qty) {
            $total += $value * $qty;
        }

        return new Money($total);
    }

    public function isEmpty(): bool
    {
        foreach ($this->coins as $qty) {
            if ($qty > 0) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, int> */
    public function toArray(): array
    {
        return $this->coins;
    }
}
