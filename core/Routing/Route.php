<?php

declare(strict_types=1);

namespace Ds\Routing;

final class Route
{
    public string $method;
    public string $path;
    public mixed $handler;
    /** @var array<string, string> */
    public array $params = [];
    /** @var list<string> */
    public array $middlewares = [];
    /** @var array<string, string> */
    public array $constraints = [];
    public ?string $routeName = null;

    /** @var (callable(self): void)|null */
    private $definitionChanged = null;

    public function __construct(string $method, string $path, mixed $handler, array $params = [])
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
        $this->params = $params;
    }

    /** @internal Router uses this to rebuild compiled metadata after fluent configuration. */
    public function onDefinitionChanged(?callable $callback): self
    {
        $this->definitionChanged = $callback;
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        $items = is_array($middleware) ? $middleware : [$middleware];
        foreach ($items as $item) {
            if (!is_string($item) || trim($item) === '') {
                throw new \InvalidArgumentException('Middleware names must be non-empty strings.');
            }
            $this->middlewares[] = trim($item);
        }
        return $this;
    }

    public function name(string $name): self
    {
        $name = trim($name);
        if ($name === '' || preg_match('/\A[A-Za-z_][A-Za-z0-9_.:-]*\z/D', $name) !== 1) {
            throw new \InvalidArgumentException('Route name must be a non-empty identifier.');
        }
        $previous = $this->routeName;
        $this->routeName = $name;
        try {
            if ($this->definitionChanged !== null) { ($this->definitionChanged)($this); }
        } catch (\\Throwable $exception) {
            $this->routeName = $previous;
            throw $exception;
        }
        return $this;
    }

    public function where(string $parameter, string $pattern): self
    {
        $parameter = trim($parameter);
        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $parameter) !== 1) {
            throw new \InvalidArgumentException('Invalid route parameter name.');
        }
        if ($pattern === '' || strlen($pattern) > 256 || preg_match('/[\x00-\x1F\x7F]/', $pattern) === 1
            || str_contains($pattern, '~')) {
            throw new \InvalidArgumentException('Invalid route parameter constraint.');
        }
        if (@preg_match('~^(?:' . $pattern . ')$~D', '') === false) {
            throw new \InvalidArgumentException('Route parameter constraint is not a valid regular expression.');
        }

        $this->constraints[$parameter] = $pattern;
        ($this->definitionChanged)?->__invoke($this);
        return $this;
    }

    public function whereNumber(string $parameter): self
    {
        return $this->where($parameter, '[0-9]+');
    }

    public function whereAlpha(string $parameter): self
    {
        return $this->where($parameter, '[A-Za-z]+');
    }

    public function whereAlphaNumeric(string $parameter): self
    {
        return $this->where($parameter, '[A-Za-z0-9]+');
    }

    public function whereUuid(string $parameter): self
    {
        return $this->where($parameter, '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-8][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}');
    }

    /** @internal A matched route is request-local; the registered definition is never mutated. */
    public function matched(array $params, ?string $method = null): self
    {
        $matched = clone $this;
        $matched->params = $params;
        if ($method !== null) {
            $matched->method = strtoupper($method);
        }
        $matched->definitionChanged = null;
        return $matched;
    }
}
