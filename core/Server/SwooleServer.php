<?php
namespace Ds\Server;

use Ds\Container\Container;

class SwooleServer
{
    protected Container $container;
    protected string $host;
    protected int $port;

    public function __construct(Container $container, string $host = '127.0.0.1', int $port = 8080)
    {
        $this->container = $container;
        $this->host = $host;
        $this->port = $port;
    }

    public function start(): void
    {
        $serverClass = '\Swoole\Http\Server';
        if (!class_exists($serverClass)) {
            echo "Swoole extension not loaded. Running in fallback mode.\n";
            return;
        }
        $server = new $serverClass($this->host, $this->port);
        $host = $this->host;
        $port = $this->port;
        $server->on('start', function () use ($host, $port) {
            echo "Swoole server started at http://{$host}:{$port}\n";
        });
        $container = $this->container;
        $server->on('request', function ($request, $response) use ($server, $container) {
            $kernel = $container->make(\App\Http\Kernel::class);
            $req = \Ds\Http\Request::fromSwoole($request);
            $resp = $kernel->handle($req);
            foreach ($resp->headers() as $name => $value) {
                $response->header($name, (string)$value);
            }
            $response->status($resp->status());
            $response->end($resp->body());
        });
        $server->start();
    }
}
