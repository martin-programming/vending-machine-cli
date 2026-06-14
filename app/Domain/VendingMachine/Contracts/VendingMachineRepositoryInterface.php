<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Contracts;

use App\Domain\VendingMachine\Aggregates\VendingMachine;

interface VendingMachineRepositoryInterface
{
    public function get(): VendingMachine;

    public function save(VendingMachine $machine): void;
}
