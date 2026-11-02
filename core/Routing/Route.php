<?php

declare(strict_types=1);

namespace Ds\Routing;

class Route
{
    public string $method;
    public string $path;
    public mixed $handler;
    public array $params = [];
    /** @var list<string> */
    public array $middlewares = [];

    public function __construct(string $method, string $path, mixed $handler, array $params = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->params = $params;
    }

    public function middleware(array|string $middleware): self
    {
        $items = is_array($middleware) ? $middleware : [$middleware];
        foreach ($items as $item) {
            if (!is_string($item) || trim($item) === '') {
                throw new \InvalidArgumentException('Middleware names must be non-empty strings.');
            }
            $this->middlewares[] = $item;
        }

        return $this;
    }
}
