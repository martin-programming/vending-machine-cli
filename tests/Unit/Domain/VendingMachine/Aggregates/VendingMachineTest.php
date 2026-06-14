<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine\Aggregates;

use App\Domain\VendingMachine\Aggregates\VendingMachine;
use App\Domain\VendingMachine\Contracts\ChangeCalculatorInterface;
use App\Domain\VendingMachine\Events\CoinInserted;
use App\Domain\VendingMachine\Events\CoinsReturned;
use App\Domain\VendingMachine\Events\MachineRestocked;
use App\Domain\VendingMachine\Events\ProductDispensed;
use App\Domain\VendingMachine\Exceptions\InsufficientChangeException;
use App\Domain\VendingMachine\Exceptions\InsufficientFundsException;
use App\Domain\VendingMachine\Exceptions\ProductOutOfStockException;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;
use PHPUnit\Framework\TestCase;

final class VendingMachineTest extends TestCase
{
    private VendingMachine $machine;

    protected function setUp(): void
    {
        $this->machine = new VendingMachine(new GreedyChangeCalculator);
        $this->machine->restock(
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
        );
        $this->machine->pullDomainEvents(); // clear service event
    }

    public function test_insert_coin_adds_to_total(): void
    {
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->insertCoin(Coin::DIME);

        $this->assertEquals(new Money(35), $this->machine->getInsertedMoney());
    }

    public function test_insert_coin_raises_event(): void
    {
        $this->machine->insertCoin(Coin::QUARTER);

        $events = $this->machine->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CoinInserted::class, $events[0]);

        /** @var CoinInserted $event */
        $event = $events[0];
        $this->assertSame(Coin::QUARTER, $event->coin);
        $this->assertEquals(new Money(25), $event->totalInserted);
    }

    public function test_select_product_with_exact_change_returns_no_change(): void
    {
        // WATER costs $0.65
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->insertCoin(Coin::DIME);
        $this->machine->insertCoin(Coin::NICKEL);

        $result = $this->machine->selectProduct(ProductSelector::WATER);

        $this->assertSame(ProductSelector::WATER, $result->product);
        $this->assertTrue($result->change->isEmpty());
        $this->assertEquals(new Money(0), $this->machine->getInsertedMoney());
    }

    public function test_select_product_returns_correct_change(): void
    {
        // Insert $1.00, buy WATER ($0.65) -> change $0.35
        $this->machine->insertCoin(Coin::DOLLAR);

        $result = $this->machine->selectProduct(ProductSelector::WATER);

        $this->assertSame(ProductSelector::WATER, $result->product);
        $this->assertEquals(new Money(35), $result->change->totalMoney());
    }

    public function test_select_product_raises_dispensed_event(): void
    {
        $this->machine->insertCoin(Coin::DOLLAR);
        $this->machine->pullDomainEvents();

        $this->machine->selectProduct(ProductSelector::WATER);

        $events = $this->machine->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductDispensed::class, $events[0]);
    }

    public function test_inserted_coins_are_cleared_after_purchase(): void
    {
        $this->machine->insertCoin(Coin::DOLLAR);
        $this->machine->selectProduct(ProductSelector::WATER);

        $this->assertEquals(new Money(0), $this->machine->getInsertedMoney());
    }

    public function test_throws_insufficient_funds(): void
    {
        $this->machine->insertCoin(Coin::QUARTER); // $0.25 < $0.65

        $this->expectException(InsufficientFundsException::class);
        $this->machine->selectProduct(ProductSelector::WATER);
    }

    public function test_throws_out_of_stock(): void
    {
        $this->machine->restock(
            CoinCollection::empty()->add(Coin::DOLLAR, 5),
            [ProductSelector::WATER->name => 0],
        );

        $this->machine->insertCoin(Coin::DOLLAR);

        $this->expectException(ProductOutOfStockException::class);
        $this->machine->selectProduct(ProductSelector::WATER);
    }

    public function test_throws_insufficient_change(): void
    {
        $this->machine->restock(
            CoinCollection::empty(), // empty vault - no change possible
            [ProductSelector::WATER->name => 5],
        );

        $this->machine->insertCoin(Coin::DOLLAR); // overpay, needs change

        $this->expectException(InsufficientChangeException::class);
        $this->machine->selectProduct(ProductSelector::WATER);
    }

    public function test_stock_decrements_after_purchase(): void
    {
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->insertCoin(Coin::DIME);
        $this->machine->insertCoin(Coin::NICKEL);

        $this->machine->selectProduct(ProductSelector::WATER);

        $product = $this->machine->getProduct(ProductSelector::WATER);
        $this->assertNotNull($product);
        $this->assertSame(4, $product->quantity());
    }

    public function test_return_coins_returns_all_inserted(): void
    {
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->insertCoin(Coin::DIME);

        $returned = $this->machine->returnCoins();

        $this->assertEquals(new Money(35), $returned->totalMoney());
        $this->assertEquals(new Money(0), $this->machine->getInsertedMoney());
    }

    public function test_return_coins_on_empty_machine_returns_empty_collection(): void
    {
        $returned = $this->machine->returnCoins();

        $this->assertTrue($returned->isEmpty());
    }

    public function test_return_coins_raises_event(): void
    {
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->pullDomainEvents();

        $this->machine->returnCoins();

        $events = $this->machine->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CoinsReturned::class, $events[0]);
    }

    public function test_service_restocks_inventory(): void
    {
        $this->machine->restock(
            CoinCollection::empty()->add(Coin::DOLLAR, 5),
            [ProductSelector::JUICE->name => 20],
        );

        $product = $this->machine->getProduct(ProductSelector::JUICE);
        $this->assertNotNull($product);
        $this->assertSame(20, $product->quantity());
    }

    public function test_service_replaces_coin_vault(): void
    {
        $newFloat = CoinCollection::empty()->add(Coin::DOLLAR, 3);
        $this->machine->restock($newFloat, []);

        $this->assertSame(3, $this->machine->getCoinVault()->quantityOf(Coin::DOLLAR));
    }

    public function test_service_raises_event(): void
    {
        $this->machine->restock(CoinCollection::empty(), []);

        $events = $this->machine->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(MachineRestocked::class, $events[0]);
    }

    public function test_pull_domain_events_clears_buffer(): void
    {
        $this->machine->insertCoin(Coin::QUARTER);
        $this->machine->pullDomainEvents();

        $this->assertEmpty($this->machine->pullDomainEvents());
    }

    public function test_uses_injected_change_calculator(): void
    {
        $alwaysEmptyCalculator = new class implements ChangeCalculatorInterface
        {
            public function calculate(Money $amount, CoinCollection $available): CoinCollection
            {
                return CoinCollection::empty();
            }
        };

        $machine = new VendingMachine($alwaysEmptyCalculator);
        $machine->restock(
            CoinCollection::empty(),
            [ProductSelector::WATER->name => 5],
        );

        // Insert exact amount so no change is needed, calculator returns empty
        $machine->insertCoin(Coin::QUARTER);
        $machine->insertCoin(Coin::QUARTER);
        $machine->insertCoin(Coin::DIME);
        $machine->insertCoin(Coin::NICKEL);

        $result = $machine->selectProduct(ProductSelector::WATER);
        $this->assertTrue($result->change->isEmpty());
    }
}
