<?php

declare(strict_types=1);

namespace Ds\Auth;

class RBAC
{
    public function can(string $role, string $permission): bool
    {
        return false;
    }
}


