<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\VendingMachine\Contracts\ChangeCalculatorInterface;
use App\Domain\VendingMachine\Contracts\VendingMachineRepositoryInterface;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use Illuminate\Support\ServiceProvider;

final class VendingMachineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChangeCalculatorInterface::class, GreedyChangeCalculator::class);

        $this->app->singleton(
            VendingMachineRepositoryInterface::class,
            InMemoryVendingMachineRepository::class,
        );
    }
}
