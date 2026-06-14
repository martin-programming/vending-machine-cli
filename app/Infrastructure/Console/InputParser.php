<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Commands\InsertCoin\InsertCoinCommand;
use App\Application\Commands\RestockMachine\RestockMachineCommand;
use App\Application\Commands\ReturnCoins\ReturnCoinsCommand;
use App\Application\Commands\SelectProduct\SelectProductCommand;
use App\Domain\VendingMachine\Exceptions\InvalidCoinException;
use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\ProductSelector;
use InvalidArgumentException;

final class InputParser
{
    private const array COIN_MAP = [
        '0.05' => Coin::NICKEL,
        '0.10' => Coin::DIME,
        '0.25' => Coin::QUARTER,
        '1.00' => Coin::DOLLAR,
    ];

    /**
     * @throws InvalidCoinException|InvalidArgumentException
     */
    public function parse(string $input): InsertCoinCommand|SelectProductCommand|ReturnCoinsCommand|RestockMachineCommand
    {
        $normalized = strtoupper(trim($input));

        if (isset(self::COIN_MAP[$input])) {
            return new InsertCoinCommand(self::COIN_MAP[$input]);
        }

        if (str_starts_with($normalized, 'GET-')) {
            $name = substr($normalized, 4);
            $selector = ProductSelector::tryFrom($name);

            if ($selector === null) {
                throw new InvalidArgumentException(sprintf('Unknown product "%s".', $name));
            }

            return new SelectProductCommand($selector);
        }

        if ($normalized === 'RETURN') {
            return new ReturnCoinsCommand;
        }

        if ($normalized === 'SERVICE') {
            return new RestockMachineCommand(
                coinFloat: self::defaultCoinFloat(),
                inventory: self::defaultInventory(),
            );
        }

        // Last-chance coin check: give a helpful error for unrecognised amounts
        if (is_numeric($input)) {
            throw new InvalidCoinException($input);
        }

        throw new InvalidArgumentException(sprintf('Unknown command "%s".', $input));
    }

    /**
     * Coin float loaded at service time: 10×NICKEL, 10×DIME, 20×QUARTER, 5×DOLLAR.
     */
    public static function defaultCoinFloat(): CoinCollection
    {
        return CoinCollection::empty()
            ->add(Coin::NICKEL, 10)
            ->add(Coin::DIME, 10)
            ->add(Coin::QUARTER, 20)
            ->add(Coin::DOLLAR, 5);
    }

    /** @return array<string, int> */
    public static function defaultInventory(): array
    {
        return [
            ProductSelector::WATER->name => 10,
            ProductSelector::JUICE->name => 10,
            ProductSelector::SODA->name => 10,
        ];
    }
}
