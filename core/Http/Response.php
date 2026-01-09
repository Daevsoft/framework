<?php

declare(strict_types=1);

namespace Ds\Http;

class Response
{
    public function __construct(
        public int $status = 200,
        public array $headers = [],
        public string $body = ''
    ) {
    }
}


