<?php

declare(strict_types=1);

namespace Ds\Server;

use Ds\Broadcasting\Connections\WebSocketConnection;
use Ds\Broadcasting\WebSocketHub;
use Ds\Container\Container;
use Ds\Database\DatabaseManager;
use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Lifecycle\LifecycleManager;
use Ds\Pool\PoolManager;
use Ds\Tasks\Scheduler;
use Ds\Tasks\TaskDispatcher;
use App\Support\RouteRegistry;
use RuntimeException;
use Throwable;

final class SwooleServer
{
    public function __construct(
        protected Container $container,
        protected string $host = '127.0.0.1',
        protected int $port = 8000,
        protected mixed $kernel = null
    ) {}

    public function start(): void
    {
        $settings = function_exists('config') ? config('server', []) : [];
        $swooleSettings = $settings['swoole'] ?? [];
        $websocketEnabled = (bool) ($settings['websocket']['enabled'] ?? false);
        $workerCount = max(1, (int) ($swooleSettings['worker_num'] ?? 1));
        $maxBroadcastBytes = max(1024, (int) ($settings['websocket']['max_broadcast_payload_bytes'] ?? 8192));
        $swooleSettings['worker_num'] = $workerCount;

        $serverClass = $websocketEnabled ? '\\Swoole\\WebSocket\\Server' : '\\Swoole\\Http\\Server';
        if (!class_exists($serverClass)) {
            throw new RuntimeException(
                $websocketEnabled
                    ? 'Swoole WebSocket server is required when server.websocket.enabled is true.'
                    : 'Swoole extension is required. Install it or explicitly select APP_RUNTIME=fpm.'
            );
        }

        $bindHost = $websocketEnabled
            ? (string) ($settings['websocket']['host'] ?? $this->host)
            : $this->host;
        $bindPort = $websocketEnabled
            ? (int) ($settings['websocket']['port'] ?? $this->port)
            : $this->port;

        $server = new $serverClass($bindHost, $bindPort);
        if (is_array($swooleSettings) && $swooleSettings !== []) $server->set($swooleSettings);

        $container = $this->container;
        $kernel = $this->kernel;
        $lifecycle = $container->make(LifecycleManager::class);
        $lifecycle->emit('booting');

        $server->on('Start', static function () use ($bindHost, $bindPort, $lifecycle, $websocketEnabled): void {
            $lifecycle->emit('booted');
            $features = $websocketEnabled ? ' (HTTP + WebSocket)' : ' (HTTP)';
            echo "Ds Framework Swoole server listening on http://{$bindHost}:{$bindPort}{$features}\n";
        });

        $server->on('Shutdown', static function () use ($lifecycle): void {
            try { $lifecycle->emit('server.shutdown'); }
            catch (Throwable $exception) { error_log((string) $exception); }
        });

        $server->on('WorkerStart', static function ($server, int $workerId) use ($lifecycle, $workerCount, $maxBroadcastBytes, $websocketEnabled): void {
            if ($workerId < $workerCount) {
                $hub = $websocketEnabled
                    ? new WebSocketHub($server, $workerId, $workerCount, $maxBroadcastBytes)
                    : new WebSocketHub();
                WebSocketHub::replaceDefault($hub);
            }
            TaskDispatcher::setServer($server);
            $lifecycle->emit('worker.start', ['server' => $server, 'worker_id' => $workerId]);
        });

        $server->on('WorkerStop', static function ($server, int $workerId) use ($container, $lifecycle): void {
            self::handleWorkerStop($container, $lifecycle, $server, $workerId);
        });

        $server->on('Request', static function ($swooleRequest, $swooleResponse) use ($container, $kernel, $lifecycle): void {
            self::handleRequest($container, $kernel, $lifecycle, $swooleRequest, $swooleResponse);
        });

        if ($websocketEnabled) {
            $server->on('PipeMessage', static function ($server, int $sourceWorkerId, mixed $message): void {
                if (is_string($message)) {
                    WebSocketHub::default()->deliverFrame($message);
                }
            });
            $server->on('Open', static function ($server, $request) use ($lifecycle): void {
                self::handleWebSocketOpen($server, $request, $lifecycle);
            });
            $server->on('Message', static function ($server, $frame): void {
                $connection = WebSocketHub::default()->get((int) $frame->fd);
                $connection?->dispatch('message', (string) ($frame->data ?? ''));
            });
            $server->on('Close', static function ($server, int $fd): void {
                $hub = WebSocketHub::default();
                $connection = $hub->get($fd);
                if ($connection !== null) {
                    $connection->dispatch('disconnect');
                    $connection->markClosed();
                    $hub->remove($fd);
                }
            });
        }

        $taskWorkers = (int) ($swooleSettings['task_worker_num'] ?? 0);
        if ($taskWorkers > 0) {
            $server->on('Task', static function ($server, int $taskId, int $srcWorkerId, mixed $data): void {
                TaskDispatcher::run($data);
            });
            $server->on('Finish', static function ($server, int $taskId, mixed $data): void {
                // Task results are intentionally optional; task handlers own durable result storage.
            });
        }

        $server->start();
    }

    private static function handleWebSocketOpen(object $server, object $request, LifecycleManager $lifecycle): void
    {
        $uri = (string) ($request->server['request_uri'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $route = RouteRegistry::getRouter()?->match('WS', $path);
        if ($route === null || !is_callable($route->handler)) {
            $server->disconnect((int) $request->fd, 1008, 'WebSocket route not found');
            return;
        }

        $hub = WebSocketHub::default();
        $connection = new WebSocketConnection($server, (int) $request->fd, $hub);
        $hub->add($connection);

        try {
            ($route->handler)($connection, $request);
            $connection->dispatch('connect', $request);
            $lifecycle->emit('websocket.open', ['connection' => $connection, 'request' => $request]);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            $hub->remove((int) $request->fd);
            $connection->close(1011, 'WebSocket handler failed');
        }
    }

    private static function handleRequest(
        Container $container,
        mixed $kernel,
        LifecycleManager $lifecycle,
        mixed $swooleRequest,
        mixed $swooleResponse
    ): void {
        $container->beginRequest();
        $context = null;
        $request = null;
        $response = null;

        try {
            $context = $container->make(Context::class);
            $request = Request::fromSwoole($swooleRequest);
            $context->set('request', $request);
            $lifecycle->emit('request.received', $request);

            $httpKernel = is_callable($kernel) ? $kernel() : ($kernel ?? $container->make(\App\Http\Kernel::class));
            $response = $httpKernel->handle($request);
            $response = Response::consumeLegacyOverrides($response);

            foreach ($response->headers() as $name => $value) $swooleResponse->header((string) $name, (string) $value);
            $swooleResponse->status($response->status());
            $swooleResponse->end($response->body());
        } catch (Throwable $exception) {
            error_log((string) $exception);
            $swooleResponse->status(500);
            $swooleResponse->end('Internal Server Error');
        } finally {
            try { $lifecycle->emit('request.handled', ['request' => $request, 'response' => $response]); }
            catch (Throwable $exception) { error_log((string) $exception); }
            try { $container->endRequest(); }
            finally {
                $context?->clear();
                Context::default()->clear();
            }
        }
    }

    private static function handleWorkerStop(
        Container $container,
        LifecycleManager $lifecycle,
        mixed $server,
        int $workerId
    ): void {
        try { $lifecycle->emit('worker.stop', ['server' => $server, 'worker_id' => $workerId]); }
        catch (Throwable $exception) { error_log((string) $exception); }

        WebSocketHub::default()->clear();
        WebSocketHub::replaceDefault(null);
        TaskDispatcher::setServer(null);

        foreach ([Scheduler::class, PoolManager::class, DatabaseManager::class] as $service) {
            try {
                $instance = $container->make($service);
                if ($instance instanceof Scheduler || $instance instanceof PoolManager) $instance->clear();
                elseif ($instance instanceof DatabaseManager) $instance->shutdown();
            } catch (Throwable $exception) { error_log((string) $exception); }
        }
    }
}
