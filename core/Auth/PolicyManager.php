<?php

declare(strict_types=1);

namespace Ds\Auth;

use InvalidArgumentException;
use Throwable;

final class PolicyManager
{
    private array $policies = [];

    public function register(string $ability, callable $policy): void
    {
        if (trim($ability) === '') throw new InvalidArgumentException('Policy ability cannot be empty.');
        $this->policies[$ability] = $policy;
    }

    public function authorize(string $ability, mixed $subject = null): bool
    {
        $policy = $this->policies[$ability] ?? null;
        if (!is_callable($policy)) return false;

        try {
            return $policy($subject) === true;
        } catch (Throwable) {
            return false;
        }
    }
}
