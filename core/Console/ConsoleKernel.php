<?php

declare(strict_types=1);

namespace Ds\Console;

class ConsoleKernel
{
    /**
     * @var array<int, class-string<Command>>
     */
    protected array $commands = [];

    public function handle(array $argv): int
    {
        // Placeholder dispatch ke command.
        return 0;
    }
}


