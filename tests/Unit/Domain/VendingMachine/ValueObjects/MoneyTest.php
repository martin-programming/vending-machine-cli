<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine\ValueObjects;

use App\Domain\VendingMachine\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_stores_cents(): void
    {
        $money = new Money(65);

        $this->assertSame(65, $money->cents);
    }

    public function test_add(): void
    {
        $result = (new Money(25))->add(new Money(40));

        $this->assertSame(65, $result->cents);
    }

    public function test_subtract(): void
    {
        $result = (new Money(100))->subtract(new Money(65));

        $this->assertSame(35, $result->cents);
    }

    public function test_is_less_than(): void
    {
        $this->assertTrue((new Money(50))->isLessThan(new Money(65)));
        $this->assertFalse((new Money(65))->isLessThan(new Money(65)));
        $this->assertFalse((new Money(100))->isLessThan(new Money(65)));
    }

    public function test_is_greater_than_or_equal(): void
    {
        $this->assertTrue((new Money(65))->isGreaterThanOrEqual(new Money(65)));
        $this->assertTrue((new Money(100))->isGreaterThanOrEqual(new Money(65)));
        $this->assertFalse((new Money(50))->isGreaterThanOrEqual(new Money(65)));
    }

    public function test_equals(): void
    {
        $this->assertTrue((new Money(65))->equals(new Money(65)));
        $this->assertFalse((new Money(65))->equals(new Money(66)));
    }

    public function test_format(): void
    {
        $this->assertSame('$0.65', (new Money(65))->format());
        $this->assertSame('$1.00', (new Money(100))->format());
        $this->assertSame('$1.50', (new Money(150))->format());
    }
}
