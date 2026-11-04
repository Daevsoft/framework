<?php

declare(strict_types=1);

namespace Ds\Foundations\Validator;

/** Result object retained for v3 custom validation callbacks. */
class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public string $error
    ) {
    }
}
