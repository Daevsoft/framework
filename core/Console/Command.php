<?php

declare(strict_types=1);

namespace Ds\Console;

abstract class Command
{
    abstract public function handle(array $argv = []): int;
}


