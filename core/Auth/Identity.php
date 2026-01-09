<?php

declare(strict_types=1);

namespace Ds\Auth;

class Identity
{
    public function __construct(
        public string $id,
        public array $roles = []
    ) {
    }
}


