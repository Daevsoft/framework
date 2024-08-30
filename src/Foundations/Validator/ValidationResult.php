<?php
namespace Ds\Foundations\Validator;

class ValidationResult
{
    public bool $isValid;
    public String $error;

    public function __construct($isValid, $error)
    {
        $this->isValid = $isValid;
        $this->error = $error;
    }
}
