<?php

declare(strict_types=1);

namespace Ds\Foundations\Validator;

/** Result object retained for v3 custom validation callbacks. */
final class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly string $error
    ) {
    }
}
