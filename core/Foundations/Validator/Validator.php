<?php

declare(strict_types=1);

namespace Ds\Foundations\Validator;

use Ds\Validation\ValidationException;

/**
 * Safe v3 namespace adapter. Validation no longer sends headers, echoes output,
 * redirects, or terminates the process; callers inspect errors or call validate().
 */
final class Validator
{
    private array $errors = [];
    private array $validated = [];

    private function __construct(
        private readonly array $input,
        private readonly array $rules
    ) {
        $this->doValidate();
    }

    public static function make(array $input, array $rules): self
    {
        ValidationProvider::boot();
        return new self($input, $rules);
    }

    public function doValidate(): bool
    {
        $this->errors = [];
        $this->validated = [];

        foreach ($this->rules as $field => $definitions) {
            $value = $this->input[$field] ?? null;
            $definitions = is_array($definitions)
                ? $definitions
                : explode('|', (string) $definitions);

            foreach ($definitions as $definition) {
                [$name, $argument] = array_pad(explode(':', (string) $definition, 2), 2, null);
                $options = $argument === null ? null : explode(',', $argument);
                $result = ValidationProvider::validate((string) $field, $value, $name, $options);

                if ($result instanceof ValidationResult && !$result->isValid) {
                    $this->errors[(string) $field] = $result->error;
                    break;
                }
            }

            if (!isset($this->errors[(string) $field]) && array_key_exists($field, $this->input)) {
                $this->validated[(string) $field] = $value;
            }
        }

        return $this->errors === [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException(array_map(
                static fn (string $message): array => [$message],
                $this->errors
            ));
        }

        return $this->validated;
    }

    public static function oldValue(string $key, mixed $default = null): mixed
    {
        return \old($key, $default);
    }
}
