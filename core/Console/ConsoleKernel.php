<?php

declare(strict_types=1);

namespace Ds\Console;

use Ds\Console\Commands\FpmServe;
use Ds\Console\Commands\MakeDomain;

class ConsoleKernel
{
    /**
     * @var array<int, class-string<Command>>
     */
    protected array $commands = [
    ];

    protected array $baseCommands = [
        FpmServe::class,
        MakeDomain::class,
    ];

    /**
     * Handle console input
     */
    public function handle(array $argv): int
    {
        if (count($argv) < 2) {
            return $this->showHelp();
        }

        $commandName = $argv[1];
        $parameters = array_slice($argv, 2);

        foreach ($this->commands as $commandClass) {
            /** @var Command $command */
            $command = new $commandClass();
            $command->setParameters($parameters);
            
            if ($command->getName() === $commandName) {
                return $command->handle();
            }
        }

        foreach ($this->baseCommands as $commandClass) {
            /** @var Command $command */
            $command = new $commandClass();
            $command->setParameters($parameters);
            
            if ($command->getName() === $commandName) {
                return $command->handle();
            }
        }

        echo "Command '{$commandName}' not found.\n";
        return $this->showHelp();
    }

    /**
     * Show available commands
     */
    protected function showHelp(): int
    {
        echo "\033[36mAvailable Commands:\033[0m\n\n";

        foreach ([...$this->commands, ...$this->baseCommands] as $commandClass) {
            /** @var Command $command */
            $command = new $commandClass();
            echo sprintf("  %-30s %s\n", $command->getName(), $command->getDescription());
        }

        echo "\n";
        return 0;
    }
}


