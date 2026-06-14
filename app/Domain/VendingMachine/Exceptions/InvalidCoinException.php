<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Exceptions;

use InvalidArgumentException;

final class InvalidCoinException extends InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('"%s" is not a valid coin denomination.', $value));
    }
}
