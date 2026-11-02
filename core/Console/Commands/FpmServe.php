<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Console\Command;
use InvalidArgumentException;

final class FpmServe extends Command
{
    protected string $name = 'fpm:serve';
    protected string $description = 'Start the PHP built-in development server using the FPM compatibility adapter';

    public function handle(): int
    {
        $host = '127.0.0.1';
        $port = 8000;

        foreach ($this->getParameters() as $argument) {
            if (str_starts_with($argument, '--host=')) {
                $host = substr($argument, 7);
            } elseif (str_starts_with($argument, '--port=')) {
                $port = substr($argument, 7);
            }
        }

        try {
            $host = $this->validateHost($host);
            $port = $this->validatePort($port);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        $_ENV['APP_RUNTIME'] = 'fpm';
        $_SERVER['APP_RUNTIME'] = 'fpm';
        putenv('APP_RUNTIME=fpm');

        $root = dirname(__DIR__, 3);
        $publicPath = $root . '/public';
        $address = str_contains($host, ':') ? '[' . $host . ']' : $host;
        $target = $address . ':' . $port;

        $command = sprintf(
            '%s -S %s -t %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($target),
            escapeshellarg($publicPath)
        );

        $this->info("Starting PHP development server with FPM adapter at http://{$target}");
        passthru($command, $exitCode);

        return $exitCode;
    }

    private function validateHost(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $host;
        }

        if (
            preg_match('/\A[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?\z/D', $host) !== 1
            || str_contains($host, '..')
        ) {
            throw new InvalidArgumentException('Invalid host. Use an IP address or a valid hostname.');
        }

        return $host;
    }

    private function validatePort(string $port): int
    {
        $validated = filter_var($port, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($validated === false) {
            throw new InvalidArgumentException('Port must be an integer between 1 and 65535.');
        }

        return $validated;
    }
}
