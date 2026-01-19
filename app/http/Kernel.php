<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\RouteRegistry;
use Ds\Routing\Router;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Http\RequestDispatcher;
use Ds\Http\Kernel as CoreKernel;

class Kernel
{
    protected CoreKernel $core;

    public function __construct(Router $router)
    {
        $dispatcher = new RequestDispatcher($router);
        $this->core = new CoreKernel(new \Ds\Middleware\MiddlewarePipeline(), $dispatcher);
    }

    public function handle(Request $request): Response
    {
        return $this->core->handle($request);
    }
}
