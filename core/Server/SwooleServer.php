<?php

declare(strict_types=1);

namespace Ds\Server;

use Ds\Container\Container;
use Ds\Context\Context;
use Ds\Http\Request;
use RuntimeException;
use Throwable;

final class SwooleServer
{
    public function __construct(
        protected Container $container,
        protected string $host = '127.0.0.1',
        protected int $port = 8000,
        protected mixed $kernel = null
    ) {
    }

    public function start(): void
    {
        $serverClass = '\\Swoole\\Http\\Server';
        if (!class_exists($serverClass)) {
            throw new RuntimeException('Swoole extension is required. Install it or explicitly select APP_RUNTIME=fpm.');
        }

        $server = new $serverClass($this->host, $this->port);
        $settings = function_exists('config') ? config('server.swoole', []) : [];
        if (is_array($settings) && $settings !== []) {
            $server->set($settings);
        }

        $host = $this->host;
        $port = $this->port;
        $server->on('start', static function () use ($host, $port): void {
            echo "Ds Framework Swoole server listening on http://{$host}:{$port}\n";
        });

        $container = $this->container;
        $kernel = $this->kernel;
        $server->on('request', static function ($swooleRequest, $swooleResponse) use ($container, $kernel): void {
            $context = $container->make(Context::class);
            $context->clear();

            try {
                $request = Request::fromSwoole($swooleRequest);
                $context->set('request', $request);

                $httpKernel = is_callable($kernel)
                    ? $kernel()
                    : ($kernel ?? $container->make(\App\Http\Kernel::class));
                $response = $httpKernel->handle($request);

                foreach ($response->headers() as $name => $value) {
                    $swooleResponse->header((string) $name, (string) $value);
                }
                $swooleResponse->status($response->status());
                $swooleResponse->end($response->body());
            } catch (Throwable $exception) {
                error_log((string) $exception);
                $swooleResponse->status(500);
                $swooleResponse->end('Internal Server Error');
            } finally {
                $context->clear();
            }
        });

        $server->start();
    }
}
