<?php

declare(strict_types=1);

namespace Ds\Validation;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(
        public readonly array $errors,
        string $message = 'The given data was invalid.'
    ) {
        parent::__construct($message, 422);
    }
}
