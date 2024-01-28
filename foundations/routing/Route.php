<?php

namespace Ds\Foundations\Routing;

use App\Middlewares\Kernel;
use Closure;
use Ds\Foundations\Common\Func;
use Ds\Foundations\Routing\Attributes\Get;
use ReflectionClass;

abstract class Route extends Kernel
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    private static array|null $middlewares;
    private static $EMPTY_ROUTE;
    private static $groupName = null;
    private static function emptyRoute(): BaseRoute
    {
        if (self::$EMPTY_ROUTE == null) {
            self::$EMPTY_ROUTE = new BaseRoute();
        }
        return self::$EMPTY_ROUTE;
    }

    private static function registerRoute($url, Closure|array $target)
    {
        if ($url == '/') {
            $url = '/index';
        }
        if (self::$groupName != null) {
            $url = self::$groupName . $url;
        }
        $route = new RouteData(
            $_SERVER['REQUEST_METHOD'],
            $url,
            self::$middlewares ?? null,
            $target
        );
        RouteProvider::addRoute($url, $route);
        return $route;
    }

    public static function get($url, Closure|array $target): BaseRoute
    {
        if ($_SERVER['REQUEST_METHOD'] == self::GET) {
            return self::registerRoute($url, $target);
        } else {
            return self::emptyRoute();
        }
    }
    public static function post($url, Closure|array $target): BaseRoute
    {
        if ($_SERVER['REQUEST_METHOD'] == self::POST) {
            return self::registerRoute($url, $target);
        } else {
            return self::emptyRoute();
        }
    }
    public static function put($url, Closure|array $target): BaseRoute
    {
        if ($_SERVER['REQUEST_METHOD'] == self::PUT) {
            return self::registerRoute($url, $target);
        } else {
            return self::emptyRoute();
        }
    }
    public static function delete($url, Closure|array $target): BaseRoute
    {
        if ($_SERVER['REQUEST_METHOD'] == self::DELETE) {
            return self::registerRoute($url, $target);
        } else {
            return self::emptyRoute();
        }
    }
    public static function middleware(array|string $middlewares, Closure $routes)
    {
        self::$middlewares = is_string($middlewares) ? [$middlewares] : $middlewares;
        $routes();
        self::$middlewares = null;
    }
    public static function group(String $name, Closure|string $routes)
    {
        self::$groupName .= '/' . trim($name, " \n\r\t\v\0/");
        if(!is_string($routes)){
            $routes();
        }else{
            self::registerRouteByClass($routes);
        }
        $lastPosition = strrpos(self::$groupName, '/');
        if($lastPosition > 1){
            self::$groupName = substr(self::$groupName, 0, $lastPosition);
        }else{
            self::$groupName = null;
        }
    }
    private static function registerRouteByClass(string $controllerName){
        $controller = new $controllerName();
        $reflectionController = new ReflectionClass($controller);
        $methods = $reflectionController->getMethods();
        $lenMethods = count($methods);
        for ($i=0; $i < $lenMethods; $i++) { 
            $method = $methods[$i];
            $attributes = $method->getAttributes(Get::class);
            if(isset($attributes[0])){
                $lenAttributes = count($attributes);
                for ($j=0; $j < $lenAttributes; $j++) { 
                    $attribute = $attributes[$j];

                    $methodName = $method->getName();
                    $attrRoute = $attribute->newInstance();
                    $attrRoute->apply($controllerName, $methodName);
                }
            }

        }
    }
}
