<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Console\Command;

final class Test extends Command
{
    protected string $name = 'test';
    protected string $description = 'Run PHPUnit tests';

    public function handle(): int
    {
        $phpunit = base_path('vendor/bin/phpunit');
        if (!is_file($phpunit)) {
            $this->error('PHPUnit is not installed. Run composer install first.');
            return 1;
        }

        $arguments = $this->parameters;
        $unitOnly = in_array('--unit', $arguments, true);
        $arguments = array_values(array_filter($arguments, static fn (string $arg): bool => $arg !== '--unit'));

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpunit);
        if ($unitOnly) {
            $command .= ' ' . escapeshellarg(base_path('tests/Unit'));
        }
        foreach ($arguments as $argument) {
            $command .= ' ' . escapeshellarg($argument);
        }

        passthru($command, $exitCode);
        return $exitCode;
    }
}
