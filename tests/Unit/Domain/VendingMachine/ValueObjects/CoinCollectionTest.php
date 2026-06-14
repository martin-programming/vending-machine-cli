<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine\ValueObjects;

use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class CoinCollectionTest extends TestCase
{
    public function test_empty_collection_has_zero_total(): void
    {
        $this->assertEquals(new Money(0), CoinCollection::empty()->totalMoney());
    }

    public function test_empty_collection_is_empty(): void
    {
        $this->assertTrue(CoinCollection::empty()->isEmpty());
    }

    public function test_add_single_coin(): void
    {
        $collection = CoinCollection::empty()->add(Coin::QUARTER);

        $this->assertSame(1, $collection->quantityOf(Coin::QUARTER));
        $this->assertEquals(new Money(25), $collection->totalMoney());
        $this->assertFalse($collection->isEmpty());
    }

    public function test_add_multiple_coins(): void
    {
        $collection = CoinCollection::empty()
            ->add(Coin::QUARTER, 2)
            ->add(Coin::DIME, 1);

        $this->assertSame(2, $collection->quantityOf(Coin::QUARTER));
        $this->assertSame(1, $collection->quantityOf(Coin::DIME));
        $this->assertEquals(new Money(60), $collection->totalMoney());
    }

    public function test_add_accumulates_same_coin(): void
    {
        $collection = CoinCollection::empty()
            ->add(Coin::NICKEL)
            ->add(Coin::NICKEL);

        $this->assertSame(2, $collection->quantityOf(Coin::NICKEL));
    }

    public function test_merge_two_collections(): void
    {
        $a = CoinCollection::empty()->add(Coin::QUARTER, 2);
        $b = CoinCollection::empty()->add(Coin::QUARTER, 1)->add(Coin::DIME, 3);

        $merged = $a->merge($b);

        $this->assertSame(3, $merged->quantityOf(Coin::QUARTER));
        $this->assertSame(3, $merged->quantityOf(Coin::DIME));
    }

    public function test_subtract_coins(): void
    {
        $vault = CoinCollection::empty()->add(Coin::QUARTER, 5)->add(Coin::DIME, 3);
        $change = CoinCollection::empty()->add(Coin::QUARTER, 2)->add(Coin::DIME, 1);

        $result = $vault->subtract($change);

        $this->assertSame(3, $result->quantityOf(Coin::QUARTER));
        $this->assertSame(2, $result->quantityOf(Coin::DIME));
    }

    public function test_subtract_throws_when_not_enough_coins(): void
    {
        $vault = CoinCollection::empty()->add(Coin::QUARTER, 1);
        $change = CoinCollection::empty()->add(Coin::QUARTER, 2);

        $this->expectException(\InvalidArgumentException::class);
        $vault->subtract($change);
    }

    public function test_immutability_add(): void
    {
        $original = CoinCollection::empty()->add(Coin::DOLLAR, 1);
        $modified = $original->add(Coin::DOLLAR, 1);

        $this->assertSame(1, $original->quantityOf(Coin::DOLLAR));
        $this->assertSame(2, $modified->quantityOf(Coin::DOLLAR));
    }

    public function test_quantity_of_absent_coin_is_zero(): void
    {
        $this->assertSame(0, CoinCollection::empty()->quantityOf(Coin::DOLLAR));
    }
}
