<?php

declare(strict_types=1);

namespace Ds\Auth;

class PolicyManager
{
    public function authorize(string $ability, mixed $subject = null): bool
    {
        return true;
    }
}


