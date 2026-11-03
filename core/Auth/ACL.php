<?php

declare(strict_types=1);

namespace Ds\Auth;

final class ACL
{
    public function __construct(private readonly array $permissions = []) {}

    public function allows(string $permission): bool
    {
        return in_array('*', $this->permissions, true)
            || in_array($permission, $this->permissions, true);
    }
}
