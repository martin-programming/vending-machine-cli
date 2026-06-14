<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Commands\InsertCoin;

use App\Application\Commands\InsertCoin\InsertCoinCommand;
use App\Application\Commands\InsertCoin\InsertCoinHandler;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\Money;
use App\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class InsertCoinHandlerTest extends TestCase
{
    private InsertCoinHandler $handler;

    protected function setUp(): void
    {
        $repository = new InMemoryVendingMachineRepository(new GreedyChangeCalculator);
        $this->handler = new InsertCoinHandler($repository);
    }

    public function test_insert_single_coin_returns_total(): void
    {
        $total = $this->handler->handle(new InsertCoinCommand(Coin::QUARTER));

        $this->assertEquals(new Money(25), $total);
    }

    public function test_insert_multiple_coins_accumulates(): void
    {
        $this->handler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->handler->handle(new InsertCoinCommand(Coin::DIME));
        $total = $this->handler->handle(new InsertCoinCommand(Coin::NICKEL));

        $this->assertEquals(new Money(40), $total);
    }

    public function test_insert_dollar(): void
    {
        $total = $this->handler->handle(new InsertCoinCommand(Coin::DOLLAR));

        $this->assertEquals(new Money(100), $total);
    }
}
