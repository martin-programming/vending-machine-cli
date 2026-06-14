<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Commands\InsertCoin\InsertCoinCommand;
use App\Application\Commands\InsertCoin\InsertCoinHandler;
use App\Application\Commands\RestockMachine\RestockMachineCommand;
use App\Application\Commands\RestockMachine\RestockMachineHandler;
use App\Application\Commands\ReturnCoins\ReturnCoinsCommand;
use App\Application\Commands\ReturnCoins\ReturnCoinsHandler;
use App\Application\Commands\SelectProduct\SelectProductCommand;
use App\Application\Commands\SelectProduct\SelectProductHandler;
use App\Domain\VendingMachine\Exceptions\InsufficientChangeException;
use App\Domain\VendingMachine\Exceptions\InsufficientFundsException;
use App\Domain\VendingMachine\Exceptions\ProductOutOfStockException;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;
use App\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end scenario tests covering the full interaction flow through the application layer.
 */
final class VendingMachineScenarioTest extends TestCase
{
    private InsertCoinHandler $insert;

    private SelectProductHandler $select;

    private ReturnCoinsHandler $return;

    private RestockMachineHandler $service;

    protected function setUp(): void
    {
        $repository = new InMemoryVendingMachineRepository(new GreedyChangeCalculator);
        $this->insert = new InsertCoinHandler($repository);
        $this->select = new SelectProductHandler($repository);
        $this->return = new ReturnCoinsHandler($repository);
        $this->service = new RestockMachineHandler($repository);

        $this->service->handle(new RestockMachineCommand(
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

    public function test_scenario_buy_water_with_exact_change(): void
    {
        $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insert->handle(new InsertCoinCommand(Coin::DIME));
        $this->insert->handle(new InsertCoinCommand(Coin::NICKEL));

        $result = $this->select->handle(new SelectProductCommand(ProductSelector::WATER));

        $this->assertSame(ProductSelector::WATER, $result->product);
        $this->assertTrue($result->change->isEmpty());
    }

    public function test_scenario_buy_water_with_dollar_receive_change(): void
    {
        $this->insert->handle(new InsertCoinCommand(Coin::DOLLAR));

        $result = $this->select->handle(new SelectProductCommand(ProductSelector::WATER));

        $this->assertSame(ProductSelector::WATER, $result->product);
        $this->assertEquals(new Money(35), $result->change->totalMoney());
        $this->assertSame(1, $result->change->quantityOf(Coin::QUARTER));
        $this->assertSame(1, $result->change->quantityOf(Coin::DIME));
    }

    public function test_scenario_multi_purchase_session(): void
    {
        // Buy JUICE exact
        $this->insert->handle(new InsertCoinCommand(Coin::DOLLAR));
        $juiceResult = $this->select->handle(new SelectProductCommand(ProductSelector::JUICE));

        $this->assertSame(ProductSelector::JUICE, $juiceResult->product);
        $this->assertTrue($juiceResult->change->isEmpty());

        // Buy SODA with $2.00 -> change $0.50
        $this->insert->handle(new InsertCoinCommand(Coin::DOLLAR));
        $this->insert->handle(new InsertCoinCommand(Coin::DOLLAR));
        $sodaResult = $this->select->handle(new SelectProductCommand(ProductSelector::SODA));

        $this->assertSame(ProductSelector::SODA, $sodaResult->product);
        $this->assertEquals(new Money(50), $sodaResult->change->totalMoney());
    }

    public function test_edge_insufficient_funds_does_not_lose_coins(): void
    {
        $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
        // $0.50 < $0.65 WATER price

        try {
            $this->select->handle(new SelectProductCommand(ProductSelector::WATER));
            $this->fail('Expected InsufficientFundsException');
        } catch (InsufficientFundsException) {
            // Coins must still be in the machine
            $returned = $this->return->handle(new ReturnCoinsCommand);
            $this->assertEquals(new Money(50), $returned->totalMoney());
        }
    }

    public function test_edge_out_of_stock_does_not_consume_coins(): void
    {
        $this->service->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()->add(Coin::QUARTER, 20),
            inventory: [ProductSelector::WATER->name => 0],
        ));

        $this->insert->handle(new InsertCoinCommand(Coin::DOLLAR));

        try {
            $this->select->handle(new SelectProductCommand(ProductSelector::WATER));
            $this->fail('Expected ProductOutOfStockException');
        } catch (ProductOutOfStockException) {
            $returned = $this->return->handle(new ReturnCoinsCommand);
            $this->assertEquals(new Money(100), $returned->totalMoney());
        }
    }

    public function test_edge_no_change_available_aborts_sale(): void
    {
        $this->service->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty(), // empty vault
            inventory: [ProductSelector::WATER->name => 5],
        ));

        $this->insert->handle(new InsertCoinCommand(Coin::DOLLAR)); // overpay

        try {
            $this->select->handle(new SelectProductCommand(ProductSelector::WATER));
            $this->fail('Expected InsufficientChangeException');
        } catch (InsufficientChangeException) {
            // Inserted coins must still be available for return
            $returned = $this->return->handle(new ReturnCoinsCommand);
            $this->assertEquals(new Money(100), $returned->totalMoney());
        }
    }

    public function test_edge_return_with_no_coins_inserted_returns_empty(): void
    {
        $returned = $this->return->handle(new ReturnCoinsCommand);

        $this->assertTrue($returned->isEmpty());
    }

    public function test_edge_vault_accumulates_inserted_coins(): void
    {
        // Service with minimal change vault but enough for exact change
        $this->service->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()
                ->add(Coin::NICKEL, 10)
                ->add(Coin::DIME, 10)
                ->add(Coin::QUARTER, 10),
            inventory: [ProductSelector::WATER->name => 10],
        ));

        // Buy twice with exact change so vault stays stable
        for ($i = 0; $i < 2; $i++) {
            $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
            $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
            $this->insert->handle(new InsertCoinCommand(Coin::DIME));
            $this->insert->handle(new InsertCoinCommand(Coin::NICKEL));
            $result = $this->select->handle(new SelectProductCommand(ProductSelector::WATER));
            $this->assertTrue($result->change->isEmpty());
        }
    }

    public function test_edge_buy_last_item_then_out_of_stock(): void
    {
        $this->service->handle(new RestockMachineCommand(
            coinFloat: CoinCollection::empty()->add(Coin::QUARTER, 10),
            inventory: [ProductSelector::WATER->name => 1],
        ));

        // Buy the last one
        $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insert->handle(new InsertCoinCommand(Coin::QUARTER));
        $this->insert->handle(new InsertCoinCommand(Coin::DIME));
        $this->insert->handle(new InsertCoinCommand(Coin::NICKEL));
        $this->select->handle(new SelectProductCommand(ProductSelector::WATER));

        // Next attempt should fail
        $this->insert->handle(new InsertCoinCommand(Coin::DOLLAR));

        $this->expectException(ProductOutOfStockException::class);
        $this->select->handle(new SelectProductCommand(ProductSelector::WATER));
    }
}
