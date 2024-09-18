<?php

namespace Ds\Foundations\Validator;

use Ds\Foundations\Network\Request;
use Ds\Foundations\Routing\RouteProvider;

class Validator
{
    private $rules;
    public function __construct($rules)
    {
        $this->rules = $rules;
    }
    public static function make($input, $rules)
    {
        // Register all validation built in when needed only
        ValidationProvider::boot();

        $validator = new Validator($rules);
        $validator->doValidate($input);
    }
    public function doValidate($input)
    {
        $result = null;
        foreach ($this->rules as $name => $rule) {
            $value = $input[$name] ?? null;
            $validate = $this->isValid($name, $value, $rule);
            if ($validate !== true) {
                $this->initResult($result);
                $result['errors'][$name] = $validate;
            }
        }
        if ($result != null) {
            $this->execResult($result);
        }
    }
    private function saveErrorFlash($result)
    {
        foreach ($result['errors'] as $key => $message) {
            set_flash('error_' . $key, $message);
        }
    }
    private function clearError()
    {
        if (isset($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                if (strstr($key, 'flash__') != false) {
                    unset($_SESSION[$key]);
                }
            }
        }
    }
    private function execResult($result)
    {
        $this->clearError();
        if (!is_null($result)) {
            if (Request::getReferrer() != null) {
                // save into flash
                $this->saveErrorFlash($result);
                header('Location:' . Request::getReferrer());
                die;
            } else {
                header('Content-Type:application/json');
                $result['message'] = 'Some fields was not valid';
                echo json_encode($result);
                die;
            }
        }
    }
    private function initResult(&$result)
    {
        if ($result == null) {
            $result = [
                'errors' => [],
                'message' => '',
            ];
        }
    }
    private function isValid($fieldName, $value, $rule)
    {
        // rules of field
        $rule = is_array($rule) ? $rule : explode('|', $rule);
        $len = count($rule);
        for ($i = 0; $i < $len; $i++) {
            $condition = $rule[$i];
            // $isBald = false; // comming soon
            $validationResult = $this->validating($fieldName, $value, $condition);
            if ($validationResult instanceof ValidationResult) {
                if (!$validationResult->isValid) {
                    return $validationResult->error;
                }
            }
        }
        return true;
    }
    private function validating($fieldName, $value, $condition): bool | ValidationResult
    {
        $rule = explode(':', $condition);
        $ruleName = $rule[0];
        $ruleOption = isset($rule[1]) ? explode('|', $rule[1]) : null;
        return ValidationProvider::validate($fieldName, $value, $ruleName, $ruleOption);
    }
}
