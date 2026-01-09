<?php

declare(strict_types=1);

namespace Ds\Http;

class Request
{
    public static function capture(): self
    {
        // Buat instance dari superglobals.
        return new self();
    }
}


