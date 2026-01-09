<?php

declare(strict_types=1);

namespace Ds\Tasks;

abstract class Task
{
    abstract public function handle(): void;
}


