<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Exceptions;

use App\Domain\VendingMachine\ValueObjects\Money;
use DomainException;

final class InsufficientFundsException extends DomainException
{
    public function __construct(Money $price, Money $inserted)
    {
        parent::__construct(sprintf(
            'Insufficient funds. Price: %s, inserted: %s.',
            $price->format(),
            $inserted->format(),
        ));
    }
}
