<?php

declare(strict_types=1);

namespace App\Application\Commands\RestockMachine;

use App\Domain\VendingMachine\ValueObjects\CoinCollection;

final readonly class RestockMachineCommand
{
    /**
     * @param  array<string, int>  $inventory  ProductSelector::name => quantity
     */
    public function __construct(
        public CoinCollection $coinFloat,
        public array $inventory,
    ) {}
}
