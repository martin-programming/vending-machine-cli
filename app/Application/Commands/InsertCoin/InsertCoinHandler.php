<?php

declare(strict_types=1);

namespace App\Application\Commands\InsertCoin;

use App\Domain\VendingMachine\Contracts\VendingMachineRepositoryInterface;
use App\Domain\VendingMachine\ValueObjects\Money;

final class InsertCoinHandler
{
    public function __construct(
        private readonly VendingMachineRepositoryInterface $repository,
    ) {}

    public function handle(InsertCoinCommand $command): Money
    {
        $machine = $this->repository->get();
        $machine->insertCoin($command->coin);
        $this->repository->save($machine);

        return $machine->getInsertedMoney();
    }
}
