<?php

namespace Ds\Foundations\Validator;

use Ds\Foundations\Provider;

class ValidationProvider implements Provider
{
    /**
     * @var ValidationRule[]
     */
    private static $errorValidation;
    private static $installed = false;

    public static function register(ValidationRule $rule, ...$others)
    {
        if (!isset(self::$errorValidation[$rule->key])) {
            self::$errorValidation[$rule->key] = $rule;
        }
        foreach ($others as $_rule) {
            self::register($_rule);
        }
    }

    public function install() {}
    public function run() {}
    public static function boot()
    {
        if (!self::$installed) {
            self::register(
                new ValidationRule('required', ' field is required', function ($value, $field, $options, ValidationRule $obj) {
                    if ($value == null || empty(trim($value))) {
                        $message = $field . $obj->message;
                        return new ValidationResult(false, $message);
                    }
                    return true;
                }),
                new ValidationRule('min', ' field cannot lower than ', function ($value, $field, $options, ValidationRule $obj) {
                    $strLength = strlen($value ?? '');
                    if ($strLength < intval($options[0])) {
                        $message = $field . $obj->message . $options[0];
                        return new ValidationResult(false, $message);
                    }
                    return true;
                }),
                new ValidationRule('max', ' field cannot higher than ', function ($value, $field, $options, ValidationRule $obj) {
                    $strLength = strlen($value ?? '');
                    if ($strLength > $options[0]) {
                        $message = $field . $obj->message . $options[0];
                        return new ValidationResult(false, $message);
                    }
                    return true;
                }),
            );
            self::$installed = true;
        }
    }
    public static function validate($field, $value, $key, $params): bool|ValidationResult
    {
        return self::$errorValidation[$key]->runValidation($value, $field, $params);
    }
}
