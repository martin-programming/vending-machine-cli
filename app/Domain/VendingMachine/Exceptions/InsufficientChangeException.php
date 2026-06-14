<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Exceptions;

use App\Domain\VendingMachine\ValueObjects\Money;
use DomainException;

final class InsufficientChangeException extends DomainException
{
    public function __construct(Money $required)
    {
        parent::__construct(sprintf(
            'Machine cannot make change for %s. Coins returned.',
            $required->format(),
        ));
    }
}
