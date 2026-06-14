<?php

declare(strict_types=1);

namespace App\Domain\VendingMachine\Exceptions;

use App\Domain\VendingMachine\ValueObjects\ProductSelector;
use DomainException;

final class ProductOutOfStockException extends DomainException
{
    public function __construct(ProductSelector $product)
    {
        parent::__construct(sprintf('%s is out of stock.', $product->label()));
    }
}
