<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine\ValueObjects;

use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class CoinTest extends TestCase
{
    public function test_backing_values(): void
    {
        $this->assertSame(5, Coin::NICKEL->value);
        $this->assertSame(10, Coin::DIME->value);
        $this->assertSame(25, Coin::QUARTER->value);
        $this->assertSame(100, Coin::DOLLAR->value);
    }

    public function test_money_conversion(): void
    {
        $this->assertEquals(new Money(5), Coin::NICKEL->money());
        $this->assertEquals(new Money(10), Coin::DIME->money());
        $this->assertEquals(new Money(25), Coin::QUARTER->money());
        $this->assertEquals(new Money(100), Coin::DOLLAR->money());
    }

    public function test_labels(): void
    {
        $this->assertSame('NICKEL', Coin::NICKEL->label());
        $this->assertSame('DIME', Coin::DIME->label());
        $this->assertSame('QUARTER', Coin::QUARTER->label());
        $this->assertSame('DOLLAR', Coin::DOLLAR->label());
    }

    public function test_all_four_cases_declared(): void
    {
        $this->assertCount(4, Coin::cases());
    }
}
