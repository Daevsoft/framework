<?php

declare(strict_types=1);

namespace Ds\Http;

class RequestDispatcher
{
    public function dispatch(Request $request): Response
    {
        // Dispatch request ke controller/handler yang tepat.
        return new Response();
    }
}


