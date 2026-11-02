<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Container\Container;
use Ds\Middleware\MiddlewareRegistry;
use Ds\Routing\Route;
use Ds\Routing\Router;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

final class RequestDispatcher
{
    /** @var array<string, ReflectionMethod> */
    private array $methodReflectionCache = [];

    /** @var array<int, ReflectionFunction> */
    private array $closureReflectionCache = [];

    public function __construct(
        protected Router $router,
        protected ?Container $container = null
    ) {
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->router->match($request->getMethod(), $request->getPath());
        if ($route === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], 'Not Found');
        }

        try {
            $destination = fn (Request $current): Response => $this->dispatchHandler($route, $current);

            // Avoid building a middleware pipeline for the common no-middleware route.
            if ($route->middlewares === []) {
                return $destination($request);
            }

            $next = $destination;
            foreach (array_reverse($route->middlewares) as $middlewareName) {
                $middleware = MiddlewareRegistry::resolve($middlewareName, $this->container);
                $downstream = $next;
                $next = static function (Request $current) use ($middleware, $downstream): Response {
                    return $middleware->handle($current, $downstream)
                        ?? Response::text('Forbidden', 403);
                };
            }

            return $next($request);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            return new Response(500, ['Content-Type' => 'text/plain; charset=utf-8'], 'Internal Server Error');
        }
    }

    private function dispatchHandler(Route $route, Request $request): Response
    {
        $handler = $route->handler;
        $routeParams = array_values($route->params);

        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $controller = $this->resolveController($handler[0]);
            $result = $this->invokeMethod($controller, (string) $handler[1], $request, $routeParams);
        } elseif (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller = $this->resolveController($class);
            $result = $this->invokeMethod($controller, $method, $request, $routeParams);
        } elseif (is_callable($handler)) {
            $result = $this->invokeCallable($handler, $request, $routeParams);
        } else {
            return new Response(500, [], 'Invalid route handler');
        }

        return $this->toResponse($result);
    }

    protected function resolveController(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Controller {$class} not found");
        }

        return $this->container !== null
            ? $this->container->make($class)
            : new $class();
    }

    protected function invokeMethod(object $controller, string $method, Request $request, array $params): mixed
    {
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException('Controller method not found: ' . $method);
        }

        $key = $controller::class . '::' . $method;
        $reflection = $this->methodReflectionCache[$key] ??= new ReflectionMethod($controller, $method);

        $first = $reflection->getParameters()[0] ?? null;
        $type = $first?->getType();
        $expectsRequest = ($type instanceof ReflectionNamedType && ($type->getName() === Request::class || is_a($type->getName(), Request::class, true)))
            || ($first !== null && in_array(strtolower($first->getName()), ['request', 'req'], true));

        return $controller->{$method}(...$this->buildArguments($reflection, $request, $params, $expectsRequest));
    }

    protected function invokeCallable(callable $handler, Request $request, array $params): mixed
    {
        if (is_array($handler)) {
            $targetClass = is_object($handler[0]) ? $handler[0]::class : (string) $handler[0];
            $key = $targetClass . '::' . $handler[1];
            $reflection = $this->methodReflectionCache[$key]
                ??= new ReflectionMethod($handler[0], $handler[1]);
        } else {
            $closure = \Closure::fromCallable($handler);
            $closure = $handler instanceof \Closure ? $handler : $closure;
            $id = spl_object_id($closure);
            $reflection = $this->closureReflectionCache[$id] ??= new ReflectionFunction($closure);
        }

        $first = $reflection->getParameters()[0] ?? null;
        $type = $first?->getType();
        $expectsRequest = ($type instanceof ReflectionNamedType && ($type->getName() === Request::class || is_a($type->getName(), Request::class, true)))
            || ($first !== null && in_array(strtolower($first->getName()), ['request', 'req'], true));

        return $handler(...$this->buildArguments($reflection, $request, $params, $expectsRequest));
    }

    protected function buildArguments(
        ReflectionMethod|ReflectionFunction $reflection,
        Request $request,
        array $params,
        bool $prependRequest
    ): array {
        $arguments = $prependRequest ? [$request] : [];
        $parameters = $reflection->getParameters();
        $offset = $prependRequest ? 1 : 0;

        foreach (array_slice($parameters, $offset) as $index => $parameter) {
            if (array_key_exists($index, $params)) {
                $value = $params[$index];
                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $value = ($type->getName() === Request::class || is_a($type->getName(), Request::class, true)) ? $request : $value;
                } elseif ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    $value = match ($type->getName()) {
                        'int' => (int) $value,
                        'float' => (float) $value,
                        'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
                        'string' => (string) $value,
                        default => $value,
                    };
                }
                $arguments[] = $value;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $arguments[] = null;
            }
        }

        return $arguments;
    }

    protected function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }
        if (is_string($result) || is_numeric($result)) {
            return Response::text((string) $result);
        }
        if ($result === null) {
            return new Response(204);
        }

        return new Response(500, [], 'Unsupported route response');
    }
}
