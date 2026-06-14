<?php

declare(strict_types=1);

namespace App\Application\Commands\RestockMachine;

use App\Domain\VendingMachine\Contracts\VendingMachineRepositoryInterface;

final class RestockMachineHandler
{
    public function __construct(
        private readonly VendingMachineRepositoryInterface $repository,
    ) {}

    public function handle(RestockMachineCommand $command): void
    {
        $machine = $this->repository->get();
        $machine->restock($command->coinFloat, $command->inventory);
        $this->repository->save($machine);
    }
}
