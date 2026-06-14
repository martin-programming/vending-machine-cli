<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Aggregates;

use App\Domain\VendingMachine\Contracts\ChangeCalculatorInterface;
use App\Domain\VendingMachine\Entities\Product;
use App\Domain\VendingMachine\Events\CoinInserted;
use App\Domain\VendingMachine\Events\CoinsReturned;
use App\Domain\VendingMachine\Events\MachineRestocked;
use App\Domain\VendingMachine\Events\ProductDispensed;
use App\Domain\VendingMachine\Exceptions\InsufficientFundsException;
use App\Domain\VendingMachine\Exceptions\ProductOutOfStockException;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\DispenseResult;
use App\Domain\VendingMachine\ValueObjects\Money;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;

final class VendingMachine
{
    private CoinCollection $insertedCoins;

    private CoinCollection $coinVault;

    /** @var array<string, Product> ProductSelector::name => Product */
    private array $inventory;

    /** @var list<object> */
    private array $domainEvents = [];

    public function __construct(private readonly ChangeCalculatorInterface $changeCalculator)
    {
        $this->insertedCoins = CoinCollection::empty();
        $this->coinVault = CoinCollection::empty();
        $this->inventory = [];
    }

    public function insertCoin(Coin $coin): void
    {
        $this->insertedCoins = $this->insertedCoins->add($coin);

        $this->domainEvents[] = new CoinInserted($coin, $this->insertedCoins->totalMoney());
    }

    public function selectProduct(ProductSelector $selector): DispenseResult
    {
        $product = $this->inventory[$selector->name] ?? null;

        if ($product === null || ! $product->isInStock()) {
            throw new ProductOutOfStockException($selector);
        }

        $price = $selector->price();
        $inserted = $this->insertedCoins->totalMoney();

        if ($inserted->isLessThan($price)) {
            throw new InsufficientFundsException($price, $inserted);
        }

        $changeAmount = $inserted->subtract($price);

        $changeCoins = $this->changeCalculator->calculate($changeAmount, $this->coinVault);

        // Absorb inserted coins into vault, pay out change
        $this->coinVault = $this->coinVault->merge($this->insertedCoins)->subtract($changeCoins);
        $this->insertedCoins = CoinCollection::empty();
        $product->dispense();

        $result = new DispenseResult($selector, $changeCoins);

        $this->domainEvents[] = new ProductDispensed($selector, $inserted, $changeCoins);

        return $result;
    }

    public function returnCoins(): CoinCollection
    {
        $returned = $this->insertedCoins;
        $this->insertedCoins = CoinCollection::empty();

        $this->domainEvents[] = new CoinsReturned($returned, $returned->totalMoney());

        return $returned;
    }

    /**
     * Restock inventory and replenish the change vault.
     *
     * @param  array<string, int>  $inventory  ProductSelector::name => quantity
     */
    public function restock(CoinCollection $coinFloat, array $inventory): void
    {
        $this->coinVault = $coinFloat;
        $this->inventory = [];

        foreach ($inventory as $name => $quantity) {
            $selector = ProductSelector::from($name);
            $this->inventory[$selector->name] = new Product($selector, $quantity);
        }

        $this->domainEvents[] = new MachineRestocked($coinFloat, count($this->inventory));
    }

    public function getInsertedMoney(): Money
    {
        return $this->insertedCoins->totalMoney();
    }

    public function getCoinVault(): CoinCollection
    {
        return $this->coinVault;
    }

    public function getProduct(ProductSelector $selector): ?Product
    {
        return $this->inventory[$selector->name] ?? null;
    }

    /** @return array<string, Product> */
    public function getInventory(): array
    {
        return $this->inventory;
    }

    /**
     * Returns and clears the pending domain events.
     *
     * @return list<object>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
