<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine\Services;

use App\Domain\VendingMachine\Exceptions\InsufficientChangeException;
use App\Domain\VendingMachine\Services\GreedyChangeCalculator;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class ChangeCalculatorTest extends TestCase
{
    private GreedyChangeCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new GreedyChangeCalculator;
    }

    public function test_zero_change_returns_empty_collection(): void
    {
        $result = $this->calculator->calculate(new Money(0), CoinCollection::empty());

        $this->assertTrue($result->isEmpty());
    }

    public function test_exact_single_coin(): void
    {
        $vault = CoinCollection::empty()->add(Coin::QUARTER, 5);
        $result = $this->calculator->calculate(new Money(25), $vault);

        $this->assertSame(1, $result->quantityOf(Coin::QUARTER));
        $this->assertEquals(new Money(25), $result->totalMoney());
    }

    public function test_greedy_uses_largest_coins_first(): void
    {
        $vault = CoinCollection::empty()
            ->add(Coin::DOLLAR, 5)
            ->add(Coin::QUARTER, 10)
            ->add(Coin::DIME, 10)
            ->add(Coin::NICKEL, 10);

        // $0.35 = 1×QUARTER + 1×DIME
        $result = $this->calculator->calculate(new Money(35), $vault);

        $this->assertSame(1, $result->quantityOf(Coin::QUARTER));
        $this->assertSame(1, $result->quantityOf(Coin::DIME));
        $this->assertSame(0, $result->quantityOf(Coin::NICKEL));
        $this->assertEquals(new Money(35), $result->totalMoney());
    }

    public function test_falls_back_to_smaller_coins_when_needed(): void
    {
        // Vault has no quarters — must use dimes
        $vault = CoinCollection::empty()
            ->add(Coin::DIME, 10)
            ->add(Coin::NICKEL, 10);

        // $0.30 = 3×DIME
        $result = $this->calculator->calculate(new Money(30), $vault);

        $this->assertSame(3, $result->quantityOf(Coin::DIME));
        $this->assertEquals(new Money(30), $result->totalMoney());
    }

    public function test_uses_available_quantity(): void
    {
        // Only 1 quarter available; remainder must come from dimes
        $vault = CoinCollection::empty()
            ->add(Coin::QUARTER, 1)
            ->add(Coin::DIME, 10);

        // $0.45 = 1×QUARTER + 2×DIME
        $result = $this->calculator->calculate(new Money(45), $vault);

        $this->assertSame(1, $result->quantityOf(Coin::QUARTER));
        $this->assertSame(2, $result->quantityOf(Coin::DIME));
        $this->assertEquals(new Money(45), $result->totalMoney());
    }

    public function test_throws_when_change_cannot_be_made(): void
    {
        $vault = CoinCollection::empty()->add(Coin::DOLLAR, 5);

        $this->expectException(InsufficientChangeException::class);
        $this->calculator->calculate(new Money(35), $vault);
    }

    public function test_throws_when_vault_is_empty(): void
    {
        $this->expectException(InsufficientChangeException::class);
        $this->calculator->calculate(new Money(10), CoinCollection::empty());
    }
}
