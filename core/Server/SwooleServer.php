<?php

declare(strict_types=1);

namespace Ds\Server;

use Ds\Container\Container;
use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Lifecycle\LifecycleManager;
use Ds\Pool\PoolManager;
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

        $container = $this->container;
        $kernel = $this->kernel;
        $lifecycle = $container->make(LifecycleManager::class);
        $lifecycle->emit('booting');

        $host = $this->host;
        $port = $this->port;
        $server->on('Start', static function () use ($host, $port, $lifecycle): void {
            $lifecycle->emit('booted');
            echo "Ds Framework Swoole server listening on http://{$host}:{$port}\n";
        });

        $server->on('WorkerStart', static function ($server, int $workerId) use ($lifecycle): void {
            $lifecycle->emit('worker.start', [
                'server' => $server,
                'worker_id' => $workerId,
            ]);
        });

        $server->on('WorkerStop', static function ($server, int $workerId) use ($container, $lifecycle): void {
            try {
                $lifecycle->emit('worker.stop', [
                    'server' => $server,
                    'worker_id' => $workerId,
                ]);
            } catch (Throwable $exception) {
                error_log((string) $exception);
            }

            try {
                $container->make(PoolManager::class)->clear();
            } catch (Throwable $exception) {
                error_log((string) $exception);
            }
        });

        $server->on('Request', static function ($swooleRequest, $swooleResponse) use ($container, $kernel, $lifecycle): void {
            $container->beginRequest();
            $context = $container->make(Context::class);
            $request = null;
            $response = null;

            try {
                $request = Request::fromSwoole($swooleRequest);
                $context->set('request', $request);
                $lifecycle->emit('request.received', $request);

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
                try {
                    $lifecycle->emit('request.handled', [
                        'request' => $request,
                        'response' => $response,
                    ]);
                } catch (Throwable $exception) {
                    error_log((string) $exception);
                }
                $container->endRequest();
            }
        });

        $server->start();
    }
}
