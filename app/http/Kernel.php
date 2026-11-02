<?php

declare(strict_types=1);

namespace App\Http;

use Ds\Container\Container;
use Ds\Http\Kernel as CoreKernel;
use Ds\Http\Request;
use Ds\Http\RequestDispatcher;
use Ds\Http\Response;
use Ds\Middleware\MiddlewarePipeline;
use Ds\Middleware\MiddlewareStack;
use Ds\Routing\Router;

final class Kernel
{
    protected CoreKernel $core;

    public function __construct(Router $router, Container $container)
    {
        $dispatcher = new RequestDispatcher($router, $container);
        $middleware = $container->make(MiddlewareStack::class);
        $this->core = new CoreKernel(new MiddlewarePipeline($middleware), $dispatcher);
    }

    public function handle(Request $request): Response
    {
        return $this->core->handle($request);
    }
}
