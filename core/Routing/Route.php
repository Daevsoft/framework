<?php

declare(strict_types=1);

namespace Ds\Routing;

class Route
{
    public string $method;
    public string $path;
    public mixed $handler;
    public array $params = [];

    public function __construct(string $method, string $path, mixed $handler, array $params = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->params = $params;
    }
}
