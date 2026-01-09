<?php

declare(strict_types=1);

namespace Ds\Http;

class ResponseEmitter
{
    public function emit(Response $response): void
    {
        // Emit HTTP response ke client (header + body).
    }
}


