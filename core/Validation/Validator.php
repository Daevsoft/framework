<?php

declare(strict_types=1);

namespace Ds\Validation;

final class Validator
{
    private array $errors = [];
    private array $validated = [];

    private function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $messages = []
    ) {
        $this->run();
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
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

    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }

        return null;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }

        return $this->validated;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $definitions) {
            $value = $this->data[$field] ?? null;
            $definitions = is_array($definitions) ? $definitions : explode('|', (string) $definitions);
            $nullable = in_array('nullable', $definitions, true);

            if ($nullable && ($value === null || $value === '')) {
                continue;
            }

            foreach ($definitions as $definition) {
                [$name, $argument] = array_pad(explode(':', (string) $definition, 2), 2, null);
                if ($name === 'nullable') {
                    continue;
                }

                $error = $this->check($name, $value, $argument);
                if ($error !== null) {
                    $messageKey = $field . '.' . $name;
                    $this->errors[$field][] = $this->messages[$messageKey] ?? $error;
                    break;
                }
            }

            if (!isset($this->errors[$field]) && array_key_exists($field, $this->data)) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function check(string $rule, mixed $value, ?string $argument): ?string
    {
        $present = $value !== null && $value !== '';
        $label = ucfirst(str_replace('_', ' ', $rule));

        return match ($rule) {
            'required' => $present && $value !== [] ? null : 'This field is required.',
            'string' => !$present || is_string($value) ? null : 'This field must be a string.',
            'integer' => !$present || filter_var($value, FILTER_VALIDATE_INT) !== false ? null : 'This field must be an integer.',
            'numeric' => !$present || is_numeric($value) ? null : 'This field must be numeric.',
            'boolean' => !$present || in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true) ? null : 'This field must be true or false.',
            'email' => !$present || (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) ? null : 'This field must be a valid email address.',
            'url' => !$present || (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false) ? null : 'This field must be a valid URL.',
            'alpha' => !$present || (is_string($value) && preg_match('/\A\p{L}+\z/u', $value) === 1) ? null : 'This field may only contain letters.',
            'alpha_num' => !$present || (is_string($value) && preg_match('/\A[\p{L}\p{N}]+\z/u', $value) === 1) ? null : 'This field may only contain letters and numbers.',
            'min' => !$present || $this->measure($value) >= (float) $argument ? null : 'This field must be at least ' . $argument . '.',
            'max' => !$present || $this->measure($value) <= (float) $argument ? null : 'This field may not be greater than ' . $argument . '.',
            'in' => !$present || in_array((string) $value, explode(',', (string) $argument), true) ? null : 'The selected value is invalid.',
            'confirmed' => !$present || $value === ($this->data['password_confirmation'] ?? null) ? null : 'The confirmation does not match.',
            'regex' => !$present || ($argument !== null && @preg_match($argument, (string) $value) === 1) ? null : 'The format is invalid.',
            default => throw new \InvalidArgumentException("Unsupported validation rule [{$rule}]."),
        };
    }

    private function measure(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_array($value) || $value instanceof \Countable) {
            return (float) count($value);
        }
        if (is_string($value)) {
            return (float) (function_exists('mb_strlen') ? mb_strlen($value) : strlen($value));
        }

        return 0.0;
    }
}
