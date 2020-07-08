<?php
class Input extends dsSystem
{
    private static $tempFormValid;
    private static $tempIsFormPassed = true;
    protected static $formErrorMessageField = [
        'required' => 'must be filled!',
        'numeric' => 'only numeric!'
    ];
    function __construct()
    {
        
    }
    public static function post($inputName, $errWarning = true)
    {
         // Get value from post value form method
        if($errWarning)
            $inputName = $_POST[$inputName];
        else
            $inputName = isset($_POST[$inputName]) ? $_POST[$inputName] : STRING_EMPTY;

        parent::fill_text($inputName);
    	return $inputName;
    }
    public static function date($inputName, $format = 'Y-m-d', $_warn = true)
    {
        $inputData = self::request($inputName, $_warn);
        $datetime = date($format, strtotime($inputData));
        return $datetime;
    }
    public static function request($inputName, $errWarning = TRUE, $validation = NULL)
    {
        $inputValue = STRING_EMPTY;
        if($errWarning)
            $inputValue = $_POST[$inputName];
        else
            $inputValue = isset($_REQUEST[$inputName]) ? $_REQUEST[$inputName] : STRING_EMPTY;

        if (!is_null($validation))
            self::checkValidation($inputValue, $inputName, $validation);
    	return $inputValue;
    }
    public static function isValid()
    {
        return self::$tempIsFormPassed['status'];
    }
    public static function getMessage()
    {
        if (!self::$tempIsFormPassed['status'])
            return self::$tempIsFormPassed['message'];
    }
    public static function isInvalid($inputName){
        if(isset(self::$tempFormValid[$inputName]))
            return !self::$tempFormValid[$inputName]['status'];
        else
            return false;
    }
    public static function value($inputName){
        if(isset(self::$tempFormValid[$inputName])){
            return self::$tempFormValid[$inputName]['value'];
        }else{
            return STRING_EMPTY;
        }
    }
    protected static function required($inputValue, $option, &$passed){
        if($passed && string_contains('required', $option))
            $passed = !string_empty($inputValue);
    }
    protected static function numeric($inputValue, $option, &$passed){
        if($passed && string_contains('numeric', $option))
            $passed = is_numeric($inputValue);
    }
    private static function checkValidation(&$inputValue, $inputName, $options)
    {
        
        $arrOption = (is_array($options)) ? $options : explode('|', $options);
        $passed = true;
        foreach ($arrOption as $option) {

            self::required($inputValue, $option, $passed);
            self::numeric($inputValue, $option, $passed);

            self::$tempFormValid[$inputName]['value'] = $inputValue;
            self::$tempFormValid[$inputName]['status'] = $passed;
            
            if(self::$tempIsFormPassed['status'])
                if(!$passed){
                    self::$tempIsFormPassed['status'] = false;
                    self::$tempIsFormPassed['message'] = self::$formErrorMessageField[$option];
                }
        }
    }
    public static function get($inputName, $errWarning = true)
    {
        // Get value from get value form method
        if($errWarning)
            $inputName = $_GET[$inputName];
        else
            $inputName = isset($_GET[$inputName]) ? $_GET[$inputName] : STRING_EMPTY;

        parent::fill_text($inputName);
        return $inputName;
    }
    public static function header($inputName, $errWarning = true)
    {
        $header = getallheaders();
        // Get value from get value form method
        $inputName = $header[$inputName];
        parent::fill_text($inputName);
        return $inputName;
    }
    public static function getArray()
    {
        $data = [];
        if (isset($_REQUEST)) {
            foreach ($_REQUEST as $key => $v) {
                $data[$key] = Input::request($key);
            }
        }
        return $data;
    }
}
