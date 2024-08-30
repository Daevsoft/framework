<?php
namespace Ds\Foundations\Validator;

use Closure;

class ValidationRule
{
    public String $key;
    public String $message;
    private Closure $action;

    public function __construct(String $key, String $message, $action)
    {
        $this->key = $key;
        $this->message = $message;
        $this->action = $action;
    }
    public function runValidation($value, $field, $options): bool | ValidationResult
    {
        return ($this->action)($value, $field, $options, $this);
    }
}
