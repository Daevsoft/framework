<?php

declare(strict_types=1);

namespace Ds\Auth;

final class RBAC
{
    public function __construct(private readonly array $permissionsByRole = []) {}

    public function can(string $role, string $permission): bool
    {
        $permissions = $this->permissionsByRole[$role] ?? [];
        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true)
            || in_array(strtok($permission, '.') . '.*', $permissions, true);
    }
}
