<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Routing\Router;

class RequestDispatcher
{
    public function __construct(protected Router $router)
    {
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->router->match($request->method, $request->path);
        if ($route !== null && is_callable($route->handler)) {
            $result = ($route->handler)($request);
            if ($result instanceof Response) {
                return $result;
            }
            return new Response(200, [], (string) $result);
        }

        return new Response(404, [], 'Not Found');
    }
}



