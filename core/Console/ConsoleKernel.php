<?php

declare(strict_types=1);

namespace Ds\Console;

class ConsoleKernel
{
    /**
     * @var array<int, class-string<Command>>
     */
    protected array $commands = [];

    /**
     * Handle console input
     */
    public function handle(array $argv): int
    {
        if (count($argv) < 2) {
            return $this->showHelp();
        }

        $commandName = $argv[1];

        foreach ($this->commands as $commandClass) {
            /** @var Command $command */
            $command = new $commandClass();
            
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

        foreach ($this->commands as $commandClass) {
            /** @var Command $command */
            $command = new $commandClass();
            echo sprintf("  %-30s %s\n", $command->getName(), $command->getDescription());
        }

        echo "\n";
        return 0;
    }
}


