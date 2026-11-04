<?php

declare(strict_types=1);

namespace Ds\Foundations\Commands\CronJob;

use Ds\Tasks\Task;

/**
 * v3-compatible job base that can also be dispatched through v4 task workers.
 *
 * Existing jobs may continue overriding start(); v4 workers invoke handle().
 */
abstract class Job extends Task
{
    protected function start(): void
    {
    }

    public function run(): void
    {
        $this->start();
    }

    final public function handle(): void
    {
        $this->run();
    }
}
