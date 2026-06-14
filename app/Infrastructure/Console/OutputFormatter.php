<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Domain\VendingMachine\ValueObjects\Coin;
use App\Domain\VendingMachine\ValueObjects\CoinCollection;
use App\Domain\VendingMachine\ValueObjects\DispenseResult;
use App\Domain\VendingMachine\ValueObjects\Money;

final class OutputFormatter
{
    public function insertedCoin(Coin $coin, Money $totalInserted): string
    {
        return sprintf(
            'Inserted %s (%s). Total: %s',
            $coin->label(),
            $coin->money()->format(),
            $totalInserted->format(),
        );
    }

    public function dispensedProduct(DispenseResult $result): string
    {
        $changeStr = $result->change->isEmpty()
            ? 'No change.'
            : sprintf('Change: %s (%s).', $result->change->totalMoney()->format(), $this->formatCoins($result->change));

        return sprintf('Dispensing %s. %s', $result->product->label(), $changeStr);
    }

    public function returnedCoins(CoinCollection $coins): string
    {
        if ($coins->isEmpty()) {
            return 'No coins inserted.';
        }

        return sprintf(
            'Returning %s (%s).',
            $coins->totalMoney()->format(),
            $this->formatCoins($coins),
        );
    }

    public function machineServiced(): string
    {
        return 'Machine serviced. Ready.';
    }

    public function error(string $message): string
    {
        return sprintf('<fg=red>Error:</> %s', $message);
    }

    public function help(): string
    {
        return implode("\n", [
            '  <fg=yellow>0.05</>  Insert NICKEL',
            '  <fg=yellow>0.10</>  Insert DIME',
            '  <fg=yellow>0.25</>  Insert QUARTER',
            '  <fg=yellow>1.00</>  Insert DOLLAR',
            '  <fg=yellow>GET-WATER | GET-JUICE | GET-SODA</>  Buy a product',
            '  <fg=yellow>RETURN</>   Return inserted coins',
            '  <fg=yellow>SERVICE</>  Restock the machine',
            '  <fg=yellow>QUIT</>     Exit',
        ]);
    }

    private function formatCoins(CoinCollection $coins): string
    {
        $parts = [];

        foreach (array_reverse(Coin::cases()) as $coin) {
            $qty = $coins->quantityOf($coin);

            if ($qty > 0) {
                $parts[] = sprintf('%d× %s', $qty, $coin->label());
            }
        }

        return implode(', ', $parts);
    }
}
