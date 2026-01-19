<?php

declare(strict_types=1);

namespace App\Console;

use Ds\Console\ConsoleKernel as BaseKernel;
use App\Console\Commands\MakeDomain;

class Kernel extends BaseKernel
{
    /**
     * @var array List of command classes
     */
    protected array $commands = [
        MakeDomain::class,
    ];

    /**
     * Define the application's command schedule
     */
    public function schedule(): void
    {
        // $this->schedule('path/to/command')
        //      ->everyMinute();
    }
}


