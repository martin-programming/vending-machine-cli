<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Commands\ReturnCoin;

use App\Application\Commands\InsertCoin\InsertCoinCommand;
use App\Application\Commands\InsertCoin\InsertCoinHandler;
use App\Application\Commands\ReturnCoins\ReturnCoinsCommand;
use App\Application\Commands\ReturnCoins\ReturnCoinsHandler;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\Money;
use App\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class ReturnCoinsHandlerTest extends TestCase
{
    private InsertCoinHandler $insertHandler;

    private ReturnCoinsHandler $returnHandler;

    protected function setUp(): void
    {
        $repository = new InMemoryVendingMachineRepository(new GreedyChangeCalculator);
        $this->insertHandler = new InsertCoinHandler($repository);
        $this->returnHandler = new ReturnCoinsHandler($repository);
    }

    public function test_returns_all_inserted_coins(): void
    {
        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DIME));

        $returned = $this->returnHandler->handle(new ReturnCoinsCommand);

        $this->assertEquals(new Money(35), $returned->totalMoney());
        $this->assertSame(1, $returned->quantityOf(Coin::QUARTER));
        $this->assertSame(1, $returned->quantityOf(Coin::DIME));
    }

    public function test_returns_empty_when_nothing_inserted(): void
    {
        $returned = $this->returnHandler->handle(new ReturnCoinsCommand);

        $this->assertTrue($returned->isEmpty());
    }

    public function test_inserted_balance_is_zero_after_return(): void
    {
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DOLLAR));
        $this->returnHandler->handle(new ReturnCoinsCommand);

        // Re-insert and check balance is only the new coin
        $total = $this->insertHandler->handle(new InsertCoinCommand(Coin::NICKEL));

        $this->assertEquals(new Money(5), $total);
    }
}
