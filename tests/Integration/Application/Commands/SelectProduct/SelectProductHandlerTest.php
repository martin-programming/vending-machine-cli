<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Commands\SelectProduct;

use App\Application\Commands\InsertCoin\InsertCoinCommand;
use App\Application\Commands\InsertCoin\InsertCoinHandler;
use App\Application\Commands\RestockMachine\RestockMachineCommand;
use App\Application\Commands\RestockMachine\RestockMachineHandler;
use App\Application\Commands\SelectProduct\SelectProductCommand;
use App\Application\Commands\SelectProduct\SelectProductHandler;
use App\Domain\VendingMachine\Exceptions\InsufficientFundsException;
use App\Domain\VendingMachine\Exceptions\ProductOutOfStockException;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;
use App\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class SelectProductHandlerTest extends TestCase
{
    private InsertCoinHandler $insertHandler;

    private SelectProductHandler $selectHandler;

    private RestockMachineHandler $restockHandler;

    protected function setUp(): void
    {
        $repository = new InMemoryVendingMachineRepository(new GreedyChangeCalculator);
        $this->insertHandler = new InsertCoinHandler($repository);
        $this->selectHandler = new SelectProductHandler($repository);
        $this->restockHandler = new RestockMachineHandler($repository);

        $this->restockHandler->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()
                ->add(Coin::NICKEL, 10)
                ->add(Coin::DIME, 10)
                ->add(Coin::QUARTER, 20)
                ->add(Coin::DOLLAR, 5),
            inventory: [
                ProductSelector::WATER->name => 5,
                ProductSelector::JUICE->name => 5,
                ProductSelector::SODA->name => 5,
            ],
        ));
    }

    public function test_buy_water_exact_change(): void
    {
        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DIME));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::NICKEL));

        $result = $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));

        $this->assertSame(ProductSelector::WATER, $result->product);
        $this->assertTrue($result->change->isEmpty());
    }

    public function test_buy_water_with_overpayment_returns_change(): void
    {
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DOLLAR));

        $result = $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));

        $this->assertEquals(new Money(35), $result->change->totalMoney());
    }

    public function test_buy_juice_with_dollar(): void
    {
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DOLLAR));

        $result = $this->selectHandler->handle(new SelectProductCommand(ProductSelector::JUICE));

        $this->assertSame(ProductSelector::JUICE, $result->product);
        $this->assertTrue($result->change->isEmpty());
    }

    public function test_throws_insufficient_funds(): void
    {
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DIME));

        $this->expectException(InsufficientFundsException::class);
        $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));
    }

    public function test_throws_when_out_of_stock(): void
    {
        $this->restockHandler->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()->add(Coin::DOLLAR, 5),
            inventory: [ProductSelector::WATER->name => 0],
        ));

        $this->insertHandler->handle(new InsertCoinCommand(Coin::DOLLAR));

        $this->expectException(ProductOutOfStockException::class);
        $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));
    }
}
