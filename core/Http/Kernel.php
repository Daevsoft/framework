<?php

declare(strict_types=1);

namespace Ds\Http;

use Ds\Middleware\MiddlewarePipeline;

final class Kernel
{
    public function __construct(
        protected MiddlewarePipeline $pipeline,
        protected RequestDispatcher $dispatcher
    ) {
    }

    public function handle(Request $request): Response
    {
        $response = $this->pipeline->handle(
            $request,
            fn (Request $current): Response => $this->dispatcher->dispatch($current)
        );

        // Enforce HEAD semantics after global middleware as well as dispatch.
        return strtoupper($request->getMethod()) === 'HEAD'
            ? $response->withBody('')
            : $response;
    }
}
