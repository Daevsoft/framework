<?php

declare(strict_types=1);

namespace Ds\Foundations\Validator;

final class ValidationProvider
{
    /** @var array<string, ValidationRule> */
    private static array $rules = [];
    private static bool $booted = false;

    public static function register(ValidationRule $rule, ValidationRule ...$others): void
    {
        self::$rules[$rule->key] ??= $rule;
        foreach ($others as $other) {
            self::$rules[$other->key] ??= $other;
        }
    }

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$rules += [
            'required' => new ValidationRule(
                'required',
                ' field is required',
                static fn (mixed $value, string $field, ?array $options, ValidationRule $rule): bool|ValidationResult =>
                    ($value !== null && $value !== [] && !(is_string($value) && trim($value) === ''))
                        ? true
                        : new ValidationResult(false, $field . $rule->message)
            ),
            'min' => new ValidationRule(
                'min',
                ' field cannot lower than ',
                static function (mixed $value, string $field, ?array $options, ValidationRule $rule): bool|ValidationResult {
                    $minimum = (int) ($options[0] ?? 0);
                    $length = is_array($value) || $value instanceof \Countable
                        ? count($value)
                        : (is_string($value) ? strlen($value) : (is_numeric($value) ? (float) $value : 0));
                    return $length >= $minimum
                        ? true
                        : new ValidationResult(false, $field . $rule->message . $minimum);
                }
            ),
            'max' => new ValidationRule(
                'max',
                ' field cannot higher than ',
                static function (mixed $value, string $field, ?array $options, ValidationRule $rule): bool|ValidationResult {
                    $maximum = (int) ($options[0] ?? PHP_INT_MAX);
                    $length = is_array($value) || $value instanceof \Countable
                        ? count($value)
                        : (is_string($value) ? strlen($value) : (is_numeric($value) ? (float) $value : 0));
                    return $length <= $maximum
                        ? true
                        : new ValidationResult(false, $field . $rule->message . $maximum);
                }
            ),
        ];

        self::$booted = true;
    }

    public static function validate(
        string $field,
        mixed $value,
        string $key,
        ?array $params = null
    ): bool|ValidationResult {
        self::boot();
        $rule = self::$rules[$key] ?? null;
        if (!$rule instanceof ValidationRule) {
            throw new \InvalidArgumentException("Unsupported validation rule [{$key}].");
        }

        return $rule->runValidation($value, $field, $params);
    }
}
