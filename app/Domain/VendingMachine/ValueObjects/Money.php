<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\ValueObjects;

final readonly class Money
{
    public function __construct(public int $cents) {}

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function isLessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function format(): string
    {
        return sprintf('$%.2f', $this->cents / 100);
    }
}
