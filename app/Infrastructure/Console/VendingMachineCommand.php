<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Commands\InsertCoin\InsertCoinCommand;
use App\Application\Commands\InsertCoin\InsertCoinHandler;
use App\Application\Commands\RestockMachine\RestockMachineCommand;
use App\Application\Commands\RestockMachine\RestockMachineHandler;
use App\Application\Commands\ReturnCoins\ReturnCoinsCommand;
use App\Application\Commands\ReturnCoins\ReturnCoinsHandler;
use App\Application\Commands\SelectProduct\SelectProductCommand;
use App\Application\Commands\SelectProduct\SelectProductHandler;
use DomainException;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class VendingMachineCommand extends Command
{
    protected $signature = 'vending-machine';

    protected $description = 'Start the interactive vending machine simulation';

    /** @var list<string> */
    private const array EXIT_COMMANDS = ['QUIT', 'EXIT', 'Q'];

    public function __construct(
        private readonly InsertCoinHandler $insertCoinHandler,
        private readonly SelectProductHandler $selectProductHandler,
        private readonly ReturnCoinsHandler $returnCoinsHandler,
        private readonly RestockMachineHandler $restockMachineHandler,
        private readonly InputParser $inputParser,
        private readonly OutputFormatter $outputFormatter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->restockMachineHandler->handle(new RestockMachineCommand(
            coinFloat: InputParser::defaultCoinFloat(),
            inventory: InputParser::defaultInventory(),
        ));

        $this->line('');
        $this->line('<fg=green>🪙  Vending Machine</>');
        $this->line('');
        $this->line($this->outputFormatter->help());
        $this->line('');

        while (true) {
            $input = $this->ask('<fg=cyan>></>');

            if (! is_string($input)) {
                break;
            }

            $trimmed = trim($input);

            if ($trimmed === '') {
                continue;
            }

            if (in_array(strtoupper($trimmed), self::EXIT_COMMANDS, true)) {
                $this->line('Goodbye!');
                break;
            }

            try {
                $this->dispatch($trimmed);
            } catch (DomainException|InvalidArgumentException $e) {
                $this->line($this->outputFormatter->error($e->getMessage()));
            }
        }

        return self::SUCCESS;
    }

    private function dispatch(string $input): void
    {
        $command = $this->inputParser->parse($input);

        if ($command instanceof InsertCoinCommand) {
            $total = $this->insertCoinHandler->handle($command);
            $this->line($this->outputFormatter->insertedCoin($command->coin, $total));

            return;
        }

        if ($command instanceof SelectProductCommand) {
            $result = $this->selectProductHandler->handle($command);
            $this->line($this->outputFormatter->dispensedProduct($result));

            return;
        }

        if ($command instanceof ReturnCoinsCommand) {
            $coins = $this->returnCoinsHandler->handle($command);
            $this->line($this->outputFormatter->returnedCoins($coins));

            return;
        }

        $this->restockMachineHandler->handle($command);
        $this->line($this->outputFormatter->machineServiced());
    }
}
