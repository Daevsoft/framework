<?php

declare(strict_types=1);

namespace App\Support;

use Ds\Routing\Router;
use InvalidArgumentException;

/**
 * Allows a resource route set to be reduced after registration:
 * Route::resource(...)->only(['index', 'show']);
 */
final class ResourceRouteRegistrar
{
    /** @param array<string, list<\Ds\Routing\Route>> $routes */
    public function __construct(
        private readonly Router $router,
        private array $routes
    ) {
    }

    /** @param list<string>|string $actions */
    public function only(array|string $actions): self
    {
        $keep = $this->normalizeActions($actions);
        foreach ($this->routes as $action => $routes) {
            if (!in_array($action, $keep, true)) {
                foreach ($routes as $route) {
                    $this->router->remove($route);
                }
                unset($this->routes[$action]);
            }
        }
        return $this;
    }

    /** @param list<string>|string $actions */
    public function except(array|string $actions): self
    {
        $remove = $this->normalizeActions($actions);
        foreach ($remove as $action) {
            foreach ($this->routes[$action] ?? [] as $route) {
                $this->router->remove($route);
            }
            unset($this->routes[$action]);
        }
        return $this;
    }

    /** @return list<string> */
    private function normalizeActions(array|string $actions): array
    {
        $actions = is_array($actions) ? $actions : [$actions];
        $allowed = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        foreach ($actions as $action) {
            if (!is_string($action) || !in_array($action, $allowed, true)) {
                throw new InvalidArgumentException('Unknown resource action [' . (string) $action . '].');
            }
        }
        return array_values(array_unique($actions));
    }
}
