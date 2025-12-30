<?php
namespace Ds\Foundations\Routing;

use App\Middlewares\Kernel;
use Closure;
use Ds\Core\Ds;
use Ds\Dir;
use Ds\Foundations\Debugger\Debug;
use Ds\Foundations\Network\Request;
use Ds\Foundations\Network\Response;
use Ds\Foundations\Provider;
use Ds\Foundations\View\PageProvider;
use ReflectionClass;
use ReflectionFunction;

class RouteProvider extends Kernel implements Provider
{
    private static array $routes;
    public static function addRoute($path, RouteData $route)
    {
        $path                = substr($path, 1);
        self::$routes[$path] = $route;
    }
    public static function assignMiddleware($path, string | array $middleware)
    {
        return self::$routes[$path]->middleware($middleware);
    }
    public function install()
    {
        $fileRoutes = Dir::$ROUTE . 'web.php';
        require_once $fileRoutes;
        // RouteProvider installed !
    }
    public function run($swooleRequest = null)
    {
        // 1. Cek apakah ini request dari Swoole atau PHP biasa
        if ($swooleRequest) {
            // Mengambil path dan method dari object Swoole $request->server
            $uri           = $swooleRequest->server['request_uri'] ?? '/';
            $currentMethod = strtoupper($swooleRequest->server['request_method'] ?? 'GET');
        } else {
            // Fallback ke PHP konvensional ($_SERVER)
            $uri           = $_SERVER['PATH_INFO'] ?? ($_SERVER['REDIRECT_URL'] ?? '/');
            $currentMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        }

        // 2. Logika routing Anda tetap sama
        if ($uri == '/') {
            $uri = '/index';
        }

        $request_uri = substr($uri, 1);

        // Pastikan findRoute mengembalikan output, jangan langsung 'echo'
        // agar bisa ditangkap oleh Swoole $response->end()
        if ($swooleRequest) {
            return $this->findRoute($request_uri, $currentMethod);
        } else {
            Response::applyToPhp();
            echo $this->findRoute($request_uri, $currentMethod);
            return null;
        }
    }
    public function findRoute(string $request, string $method = 'GET')
    {
        $route_arr = self::$routes;

        $arr_request = explode('/', $request);
        $rqc         = count($arr_request);
        $iterate     = 0;
        foreach ($route_arr as $route => $callback) {
            $arr_route = explode('/', $route);
            $args      = [];

            $isRight = 0;
            $rtc     = count($arr_route);
            // Skip routes that don't match the current HTTP method
            if (isset($callback->method) && strtoupper($callback->method) != strtoupper($method)) {
                continue;
            }

            if ($rtc == $rqc) {
                for ($i = 0; $i < $rtc; $i++) {
                    if ($arr_route[$i][0] != '{') {
                        if ($arr_route[$i] == $arr_request[$i]) {
                            $isRight++;
                        }
                    } else {
                        $isRight++;
                        $argName        = trim($arr_route[$isRight - 1], '{}');
                        $argValue       = $arr_request[$isRight - 1];
                        $args[$argName] = $argValue;
                    }
                    $iterate++;
                }
            }
            if ($isRight == $rtc) {
                return $this->executeRoute($callback, $args);
                break;
            }
        }
        return PageProvider::page_not_found();
    }
    public function validateMiddleware(RouteData $route, Request $request): Response
    {
        $middlewares = null;
        if (is_string($route->middlewares)) {
            $middlewares = [$route->middlewares];
        } else if (is_array($route->middlewares)) {
            $middlewares = $route->middlewares;
        }
        // execute middleware
        $countMiddlewares = count($middlewares);
        $continue         = new Response(true, $request);
        for ($i = 0; $i < $countMiddlewares; $i++) {
            $middlewareName = explode(':', $middlewares[$i]);
            $mName          = $middlewareName[0];
            $mOptions       = count($middlewareName) > 1 ? array_splice($middlewareName, 1) : null;
            if (! isset($this->middlewareAlias[$mName])) {
                // TODO Error middleware not registered
                echo 'Middleware \'' . $mName . '\' not registered!';
                die();
            }
            $classM          = new $this->middlewareAlias[$mName]();
            $classM->options = ($mOptions != null) ? explode(',', $mOptions[0]) : null;
            $continue        = $classM->handle($continue->request, function (Request $request = new Request()) {
                return new Response(true, $request);
            }) ?? new Response(false);

            if (! $continue->isValid) {
                echo 'Stopped by middleware validation';
                die();
            }
        }
        return $continue;
    }
    public function executeRoute(RouteData $route, array $params = [])
    {
        if ($route instanceof RouteData) {
            $middlewareResponse = new Response();
            if ($route->middlewares != null) {
                $middlewareResponse->request = new Request();
                $middlewareResponse          = $this->validateMiddleware($route, $middlewareResponse->request);
                if (! $middlewareResponse->isValid) {
                    return; // TODO Route Validation Result
                }
            }
            $response = null;
            if (is_array($route->target)) {
                $className  = $route->target[0];
                $methodName = $route->target[1];
                // Instance of Controller
                $obj              = new $className();
                $route->target[0] = $obj;

                $reflectionFunction = new ReflectionClass($obj);
                $reflectMethod      = $reflectionFunction->getMethod($methodName);
                $response           = $this->responseReflector($reflectMethod, $route, $params);
            } else if ($route->target instanceof Closure) {
                $reflectionFunction = new ReflectionFunction($route->target);
                $response           = $this->responseReflector($reflectionFunction, $route, $params);
            }
            return $this->response(value: $response);
        }
    }
    private function responseReflector($reflector, $route, $params)
    {
        $parameters      = $reflector->getParameters();
        $totalParameters = $reflector->getNumberOfParameters();
        return $this->routeResponse($route->target, $parameters, $totalParameters, $params);
    }
    private function routeResponse($target, &$parameters, $totalParameters, $routeParams)
    {
        for ($iParam = 0; $iParam < $totalParameters; $iParam++) {
            $paramType = $parameters[$iParam]->getType();
            if ($paramType != null && class_exists($paramType->getName())) {
                $parameters[$iParam] = $this->createInstance($paramType->getName());
            } else {
                $paramName           = $parameters[$iParam]->getName();
                $parameters[$iParam] = $routeParams[$paramName] ?? null;
            }
        }
        return call_user_func_array($target, $parameters);
    }
    private function createInstance($className)
    {
        $instance = new ReflectionClass($className);
        return $instance->newInstance();
    }
    public function response($value)
    {
        if (is_array($value) || is_object($value)) {
            Debug::disabled();
            Response::header('Content-Type:application/json');
            return json_encode($value);
        } else {
            return $value;
        }
    }
}
