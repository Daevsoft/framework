<?php

namespace Ds\Foundations\Network;

use Ds\Helper\Str;

class Validator
{
    private $rules;
    private static $errorValidation = [
        'required' => ' field is required',
        'min' => ' field cannot lower than ',
        'max' => ' field cannot higher than ',
    ];
    public function __construct($rules)
    {
        $this->rules = $rules;
    }
    public static function make($input, $rules)
    {
        $validator = new Validator($rules);
        $result = $validator->doValidate($input);
        if (!is_null($result)) {
            if (isset($_REQUEST['HTTP_REFERRER'])) {
                header('Location:' . $_REQUEST['HTTP_REFERRER']);
                die;
            } else {
                header('Content-Type:application/json');
                $result['message'] = 'Some fields was not valid';
                echo json_encode($result);
                die;
            }
        }
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
        return $result;
    }
    private function initResult(&$result)
    {
        if ($result === null) {
            $result = [
                'errors' => [],
                'message' => '',
            ];
        }
    }
    private function isValid($name, $value, $rule)
    {
        // rules of field
        $rule = is_array($rule) ? $rule : explode('|', $rule);
        $len = count($rule);
        for ($i = 0; $i < $len; $i++) {
            $condition = $rule[$i];
            // $isBald = false; // comming soon
            $error = null;
            if ($this->validating($value, $condition, $error)) {
                continue;
            } else {
                return $name . $error;
            }
        }
        return true;
    }
    private function validating($value, $condition, &$error)
    {
        if ($condition == 'required') {
            if ($value === null || empty(trim($value))) {
                $error = self::$errorValidation[$condition];
                return false;
            }
        } else if (Str::contains($condition, ':')) {
            $rule = explode(':', $condition);
            $ruleName = $rule[0];
            $ruleOption = $rule[1];
            if ($ruleName == 'min') {
                $strLength = strlen($value ?? '');
                if ($strLength < intval($ruleOption)) {
                    $error = self::$errorValidation[$ruleName] . $ruleOption;
                    return false;
                }
            } else if ($ruleName == 'max') {
                $strLength = strlen($value ?? '');
                if ($strLength > $ruleOption) {
                    $error = self::$errorValidation[$ruleName] . $ruleOption;
                    return false;
                }
            }
        }
        return true;
    }
}
