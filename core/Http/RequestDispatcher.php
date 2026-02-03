<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Container\Container;
use Ds\Routing\Router;
use ReflectionClass;

class RequestDispatcher
{
    public function __construct(protected Router $router, public ?Container $container)
    {
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->router->match($request->method, $request->path);
        
        if (is_array($route->handler)) { $controller = $route->handler[0]; $method = $route->handler[1]; // Ask the container to build the controller 
        if ($this->container->has($controller)) { $instance = $this->container->get($controller); } elseif (class_exists($controller)) { // Fallback: let container auto-resolve constructor dependencies 
        $reflector = new ReflectionClass($controller); $constructor = $reflector->getConstructor(); if ($constructor) { $params = []; foreach ($constructor->getParameters() as $param) { $type = $param->getType(); if ($type && !$type->isBuiltin()) { $dependencyClass = $type->getName(); // resolve from container 
        if ($this->container->has($dependencyClass)) { $params[] = $this->container->get($dependencyClass); } else { // fallback: new instance 
        $params[] = new $dependencyClass(); } } else { // optional params or scalars 
        $params[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null; } } $instance = $reflector->newInstanceArgs($params); } else { $instance = new $controller(); } } else { return new Response(500, [], "Controller {$controller} not found"); } if (!method_exists($instance, $method)) { return new Response(500, [], "Method {$method} not found in {$controller}"); } $result = $instance->$method($request); }
   
        return new Response(404, [], 'Not Found');
    }
}



