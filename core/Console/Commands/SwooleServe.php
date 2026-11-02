<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use Ds\Console\Command;

final class SwooleServe extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start the primary Swoole HTTP server';

    public function handle(): int
    {
        $root = dirname(__DIR__, 3);

        // The primary serve command must not be silently redirected to FPM.
        $_ENV['APP_RUNTIME'] = 'swoole';
        $_SERVER['APP_RUNTIME'] = 'swoole';
        putenv('APP_RUNTIME=swoole');

        require $root . '/app/bootstrap/app.php';
        return 0;
    }
}
