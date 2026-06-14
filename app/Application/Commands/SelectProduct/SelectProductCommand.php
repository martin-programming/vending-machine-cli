<?php

declare(strict_types=1);

namespace App\Application\Commands\SelectProduct;

use App\Domain\VendingMachine\ValueObjects\ProductSelector;

final readonly class SelectProductCommand
{
    public function __construct(public ProductSelector $selector) {}
}
