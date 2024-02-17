<?php

namespace Ds\Foundations\Routing;

use App\Middlewares\Kernel;
use Closure;
use Ds\Dir;
use Ds\Foundations\Common\File;
use Ds\Foundations\Common\Func;
use Ds\Foundations\Debugger\Debug;
use Ds\Foundations\Network\Request;
use Ds\Foundations\Network\Response;
use Ds\Foundations\Provider;
use Reflection;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\VarDumper\VarDumper;

class RouteProvider extends Kernel implements Provider
{
    private static array $routes;
    public static function addRoute($path, RouteData $options)
    {
        $path = substr($path, 1);
        self::$routes[$path] = $options;
    }
    public static function assignMiddleware($path, string|array $middleware)
    {
        return self::$routes[$path]->middleware($middleware);
    }
    function install()
    {
        $fileRoutes = Dir::$ROUTE . 'web.php';
        require_once $fileRoutes;
        // RouteProvider installed !
    }
    function run()
    {
        // RouteProvider running..
        $uri = $_SERVER['PATH_INFO'] ?? '/';
        if ($uri == '/') {
            $uri = '/index';
        }
        $request_uri = substr($uri, 1);
        $this->findRoute($request_uri);
    }
    public function findRoute(string $request)
    {
        $route_arr = self::$routes;

        $arr_request = explode('/', $request);
        $rqc = count($arr_request);
        $iterate = 0;
        foreach ($route_arr as $route => $callback) {
            $arr_route = explode('/', $route);
            $args = [];

            $isRight = 0;
            $rtc = count($arr_route);
            if ($rtc == $rqc) {
                for ($i = 0; $i < $rtc; $i++) {
                    if ($arr_route[$i][0] != '{') {
                        if ($arr_route[$i] == $arr_request[$i]) {
                            $isRight++;
                        }
                    } else {
                        $isRight++;
                        $argName = trim($arr_route[$isRight - 1], '{}');
                        $argValue = $arr_request[$isRight - 1];
                        $args[$argName] = $argValue;
                    }
                    $iterate++;
                }
            }
            if ($isRight == $rtc) {
                $this->executeRoute($callback, $args);
                break;
            }
        }
    }
    public function validateMiddleware(RouteData $route, Request $request):Response
    {
        $middlewares = null;
        if (is_string($route->middlewares)) {
            $middlewares = [$route->middlewares];
        } else if (is_array($route->middlewares)) {
            $middlewares = $route->middlewares;
        }
        // execute middleware 
        $countMiddlewares = count($middlewares);
        $continue = new Response(true, $request);
        for ($i = 0; $i < $countMiddlewares; $i++) {
            $mName = $middlewares[$i];
            if (!isset($this->middlewareAlias[$mName])) {
                // TODO Error middleware not registered
                echo 'Middleware \'' . $mName . '\' not registered!';
                die();
            }
            $classM = new $this->middlewareAlias[$mName]();
            $continue = $classM->handle($continue->request, function (Request $request = new Request()) {
                return new Response(true, $request);
            }) ?? new Response(false);

            if (!$continue->isValid) {
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
                $middlewareResponse = $this->validateMiddleware($route, $middlewareResponse->request);
                if (!$middlewareResponse) {
                    return; // TODO Route Validation
                }
            }
            $response = null;
            if (is_array($route->target)) {
                $className = $route->target[0];
                $methodName = $route->target[1];
                // Instance of Controller
                $obj = new $className();
                $route->target[0] = $obj;
                // die();
                $reflectionFunction = new ReflectionClass($obj);
                $reflectMethod = $reflectionFunction->getMethod($methodName);
                $parameters = $reflectMethod->getParameters();
                $totalParameters = $reflectMethod->getNumberOfParameters();
                $this->assignRequest($middlewareResponse->request, $parameters, $totalParameters, $params);
                $response = call_user_func_array($route->target, $params);
            } else if ($route->target instanceof Closure) {
                $reflectionFunction = new ReflectionFunction($route->target);
                $parameters = $reflectionFunction->getParameters();
                $totalParameters = $reflectionFunction->getNumberOfParameters();
                $this->assignRequest($middlewareResponse->request, $parameters, $totalParameters, $params);
                $response = call_user_func_array($route->target, $params);
            }
            $this->response($response);
        }
    }
    private function assignRequest(Request $request, array $parameters, int $totalParameters, array &$params){
        for ($i=0; $i < $totalParameters; $i++) { 
            $p = $parameters[$i]->name;
            if($p == 'request'){
                $params['request'] = $request;
                break;
            }
        }
    }
    function response($value)
    {
        if (is_array($value) || is_object($value)) {
            Debug::disabled();
            header('Content-Type:application/json');
            echo json_encode($value);
        } else {
            echo $value;
        }
    }
}
