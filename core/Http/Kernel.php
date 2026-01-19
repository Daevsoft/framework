<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Middleware\MiddlewarePipeline;
use Ds\Http\RequestDispatcher;

class Kernel
{
    public function __construct(
        protected MiddlewarePipeline $pipeline = new MiddlewarePipeline(),
        protected RequestDispatcher $dispatcher
    ) {}

    public function handle(Request $request): Response
    {
        // Orkestrasi lifecycle request → response via middleware, then dispatch.
        return $this->pipeline->handle($request, function() use ($request) {
            return $this->dispatcher->dispatch($request);
        });
    }
}



