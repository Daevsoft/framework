<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Middleware\MiddlewarePipeline;

class Kernel
{
    public function __construct(
        protected MiddlewarePipeline $pipeline = new MiddlewarePipeline()
    ) {
    }

    public function handle(Request $request): Response
    {
        // Orkestrasi lifecycle request → response.
        return $this->pipeline->handle($request, fn () => new Response());
    }
}


