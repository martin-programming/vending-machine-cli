<?php

declare(strict_types=1);

namespace App\Application\Commands\InsertCoin;

use App\Domain\VendingMachine\ValueObjects\Coin;

final readonly class InsertCoinCommand
{
    public function __construct(public Coin $coin) {}
}
