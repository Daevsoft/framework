<?php

declare(strict_types=1);

namespace Ds\Auth;

class ACL
{
    public function allows(string $permission): bool
    {
        return false;
    }
}


