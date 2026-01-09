<?php

declare(strict_types=1);

namespace Ds\Auth;

class AuthManager
{
    public function guard(string $name = 'default'): Guard
    {
        return new Guard();
    }
}


