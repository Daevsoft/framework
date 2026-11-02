<?php

declare(strict_types=1);

namespace Ds\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;
use UnexpectedValueException;

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
        if (!$result instanceof Response) {
            throw new UnexpectedValueException('Every middleware and HTTP handler must return a Response instance.');
        }

        return $result;
    }
}


