<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Config\ConfigRepository;
use Ds\Console\Command;
use Throwable;

final class Config extends Command
{
    protected string $name = 'config';
    protected string $description = 'Build or clear the PHP configuration cache';

    public function handle(): int
    {
        try {
            if (in_array('--clear', $this->parameters, true)) {
                ConfigRepository::clear();
                $this->info('Configuration cache cleared.');
                return 0;
            }

            $configs = ConfigRepository::cache();
            $this->info('Configuration cached (' . count($configs) . ' files).');
            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
