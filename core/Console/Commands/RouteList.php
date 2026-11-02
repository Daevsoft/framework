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

            echo sprintf("%-10s %-42s %s\n", 'METHOD', 'URI', 'HANDLER');
            foreach ($router->all() as $route) {
                $handler = is_array($route->handler)
                    ? implode('@', $route->handler)
                    : (is_string($route->handler) ? $route->handler : 'Closure');
                echo sprintf("%-10s %-42s %s\n", $route->method, $route->path, $handler);
            }

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
