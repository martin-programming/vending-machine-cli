<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Commands\RestockMachine;

use App\Application\Commands\InsertCoin\InsertCoinCommand;
use App\Application\Commands\InsertCoin\InsertCoinHandler;
use App\Application\Commands\RestockMachine\RestockMachineCommand;
use App\Application\Commands\RestockMachine\RestockMachineHandler;
use App\Application\Commands\SelectProduct\SelectProductCommand;
use App\Application\Commands\SelectProduct\SelectProductHandler;
use App\Domain\VendingMachine\Exceptions\ProductOutOfStockException;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;
use App\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class RestockMachineHandlerTest extends TestCase
{
    private RestockMachineHandler $restockHandler;

    private InsertCoinHandler $insertHandler;

    private SelectProductHandler $selectHandler;

    protected function setUp(): void
    {
        $repository = new InMemoryVendingMachineRepository(new GreedyChangeCalculator);
        $this->restockHandler = new RestockMachineHandler($repository);
        $this->insertHandler = new InsertCoinHandler($repository);
        $this->selectHandler = new SelectProductHandler($repository);
    }

    public function test_restock_makes_products_available(): void
    {
        $this->restockHandler->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()->add(Coin::QUARTER, 10),
            inventory: [ProductSelector::WATER->name => 3],
        ));

        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DIME));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::NICKEL));

        $result = $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));

        $this->assertSame(ProductSelector::WATER, $result->product);
    }

    public function test_restock_replaces_previous_inventory(): void
    {
        // Stock WATER, sell it out
        $this->restockHandler->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()->add(Coin::NICKEL, 20),
            inventory: [ProductSelector::WATER->name => 1],
        ));

        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DIME));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::NICKEL));
        $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));

        // Restock
        $this->restockHandler->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()->add(Coin::NICKEL, 20),
            inventory: [ProductSelector::WATER->name => 5],
        ));

        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::DIME));
        $this->insertHandler->handle(new InsertCoinCommand(Coin::NICKEL));
        $result = $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));

        $this->assertSame(ProductSelector::WATER, $result->product);
    }

    public function test_restock_removes_unspecified_products(): void
    {
        $this->restockHandler->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()->add(Coin::DOLLAR, 5),
            inventory: [ProductSelector::JUICE->name => 5], // WATER not included
        ));

        $this->insertHandler->handle(new InsertCoinCommand(Coin::DOLLAR));

        $this->expectException(ProductOutOfStockException::class);
        $this->selectHandler->handle(new SelectProductCommand(ProductSelector::WATER));
    }
}
