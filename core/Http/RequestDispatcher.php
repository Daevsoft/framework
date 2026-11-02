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
        $routeParams = $route->params;

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

        return $controller->{$method}(...$this->buildArguments($reflection, $request, $params));
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

        return $handler(...$this->buildArguments($reflection, $request, $params));
    }

    /**
     * Bind route values by parameter name first, then by declaration order.
     * Request injection is type-based and may appear before or after route values.
     *
     * @param array<string|int, mixed> $params
     */
    protected function buildArguments(
        ReflectionMethod|ReflectionFunction $reflection,
        Request $request,
        array $params
    ): array {
        $arguments = [];
        $positional = array_values($params);
        $routeIndex = 0;

        foreach ($reflection->getParameters() as $index => $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
            $isRequest = $typeName !== null
                && !$type->isBuiltin()
                && ($typeName === Request::class || is_a($typeName, Request::class, true));

            // Preserve the conventional untyped $request / $req first argument.
            if ($isRequest || ($index === 0 && in_array(strtolower($parameter->getName()), ['request', 'req'], true))) {
                $arguments[] = $request;
                continue;
            }

            if (array_key_exists($parameter->getName(), $params)) {
                $value = $params[$parameter->getName()];
                $routeIndex++;
                $arguments[] = $this->castRouteValue($value, $type);
                continue;
            }

            if (array_key_exists($routeIndex, $positional)) {
                $value = $positional[$routeIndex++];
                $arguments[] = $this->castRouteValue($value, $type);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $arguments[] = null;
            }
        }

        return $arguments;
    }

    private function castRouteValue(mixed $value, mixed $type): mixed
    {
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'string' => (string) $value,
            default => $value,
        };
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
