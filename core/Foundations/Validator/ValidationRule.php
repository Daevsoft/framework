<?php

declare(strict_types=1);

namespace Ds\Foundations\Validator;

use Closure;

final class ValidationRule
{
    public readonly string $key;
    public readonly string $message;
    private readonly Closure $action;

    public function __construct(string $key, string $message, callable $action)
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Validation rule key cannot be empty.');
        }

        $this->key = $key;
        $this->message = $message;
        $this->action = Closure::fromCallable($action);
    }

    public function runValidation(
        mixed $value,
        string $field,
        ?array $options
    ): bool|ValidationResult {
        return ($this->action)($value, $field, $options, $this);
    }
}
