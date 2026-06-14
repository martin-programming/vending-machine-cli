<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\VendingMachine\Aggregates\VendingMachine;
use App\Domain\VendingMachine\Contracts\ChangeCalculatorInterface;
use App\Domain\VendingMachine\Contracts\VendingMachineRepositoryInterface;

final class InMemoryVendingMachineRepository implements VendingMachineRepositoryInterface
{
    private ?VendingMachine $machine = null;

    public function __construct(private readonly ChangeCalculatorInterface $changeCalculator) {}

    public function get(): VendingMachine
    {
        if ($this->machine === null) {
            $this->machine = new VendingMachine($this->changeCalculator);
        }

        return $this->machine;
    }

    public function save(VendingMachine $machine): void
    {
        $this->machine = $machine;
    }
}
