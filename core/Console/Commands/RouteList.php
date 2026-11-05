<?php

declare(strict_types=1);

namespace Ds\Console\Commands;

use App\Support\RouteRegistry;
use Ds\Console\Command;
use Ds\Routing\Router;
use Throwable;

final class RouteList extends Command
{
    protected string $name = 'route:list';
    protected string $description = 'List routes registered by the application';

    public function handle(): int
    {
        try {
            $router = new Router();
            RouteRegistry::setRouter($router);
            require base_path('app/routes/web.php');
            require base_path('app/routes/api.php');
            require base_path('app/routes/websocket.php');

            $routes = $router->all();
            usort($routes, static fn ($left, $right): int =>
                [$left->method, $left->path] <=> [$right->method, $right->path]
            );

            echo sprintf("%-9s %-34s %-24s %-34s %s\n", 'METHOD', 'URI', 'NAME', 'HANDLER', 'MIDDLEWARE');
            foreach ($routes as $route) {
                $handler = is_array($route->handler)
                    ? implode('@', array_map(
                        static fn (mixed $part): string => is_object($part) ? $part::class : (string) $part,
                        $route->handler
                    ))
                    : (is_string($route->handler) ? $route->handler : 'Closure');
                $middleware = implode(',', $route->middlewares);
                $constraints = [];
                foreach ($route->constraints as $parameter => $pattern) {
                    $constraints[] = $parameter . '=' . $pattern;
                }
                $details = $middleware;
                if ($constraints !== []) {
                    $details .= ($details === '' ? '' : ' ') . 'where:' . implode(',', $constraints);
                }

                echo sprintf(
                    "%-9s %-34s %-24s %-34s %s\n",
                    $route->method,
                    $route->path,
                    $route->routeName ?? '-',
                    $handler,
                    $details === '' ? '-' : $details
                );
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
