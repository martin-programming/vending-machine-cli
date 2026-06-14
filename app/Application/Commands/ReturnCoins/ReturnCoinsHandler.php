<?php

declare(strict_types=1);

namespace App\Application\Commands\ReturnCoins;

use App\Domain\VendingMachine\Contracts\VendingMachineRepositoryInterface;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;

final class ReturnCoinsHandler
{
    public function __construct(
        private readonly VendingMachineRepositoryInterface $repository,
    ) {}

    public function handle(ReturnCoinsCommand $command): CoinCollection
    {
        $machine = $this->repository->get();
        $coins = $machine->returnCoins();
        $this->repository->save($machine);

        return $coins;
    }
}
