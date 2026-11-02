<?php

declare(strict_types=1);

namespace Ds\Console;

use Ds\Console\Commands\FpmServe;
use Ds\Console\Commands\Config;
use Ds\Console\Commands\SwooleServe;
use Ds\Console\Commands\MakeDomain;
use Ds\Console\Commands\AddController;
use Ds\Console\Commands\AddModel;
use Ds\Console\Commands\AddView;
use Ds\Console\Commands\AddTest;
use Ds\Console\Commands\AddMigration;
use Ds\Console\Commands\RouteList;
use Ds\Console\Commands\Test;
use Ds\Console\Commands\Migrate;

class ConsoleKernel
{
    /**
     * @var array<int, class-string<Command>>
     */
    protected array $commands = [
    ];

    protected array $baseCommands = [
        SwooleServe::class,
        FpmServe::class,
        MakeDomain::class,
        Migrate::class,
        Config::class,
        AddController::class,
        AddModel::class,
        AddView::class,
        AddTest::class,
        AddMigration::class,
        RouteList::class,
        Test::class,
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


