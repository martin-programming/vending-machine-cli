<?php

declare(strict_types=1);

namespace App\Application\Commands\SelectProduct;

use App\Domain\VendingMachine\Contracts\VendingMachineRepositoryInterface;
use App\Domain\VendingMachine\ValueObjects\DispenseResult;

final class SelectProductHandler
{
    public function __construct(
        private readonly VendingMachineRepositoryInterface $repository,
    ) {}

    public function handle(SelectProductCommand $command): DispenseResult
    {
        $machine = $this->repository->get();
        $result = $machine->selectProduct($command->selector);
        $this->repository->save($machine);

        return $result;
    }
}
