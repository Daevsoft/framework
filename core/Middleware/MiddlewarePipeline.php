<?php

declare(strict_types=1);

namespace Ds\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;

class MiddlewarePipeline
{
    public function __construct(
        protected MiddlewareStack $stack = new MiddlewareStack()
    ) {
    }

    public function handle(Request $request, callable $destination): Response
    {
        $runner = array_reduce(
            array_reverse($this->stack->all()),
            fn ($next, $middleware) => fn ($req) => $middleware($req, $next),
            $destination
        );

        $result = $runner($request);
        return $result instanceof Response ? $result : new Response();
    }
}


