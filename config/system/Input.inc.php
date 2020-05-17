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
    private static function warn($inputName)
    {
        parent::MessageError(uri(0).Key::CHAR_SLASH.uri(1),'Input Name : '. $inputName .' not found!');
        die();
    }
    public static function post($inputName, $_warn = true)
    {
        // Check is Input Key able
        if($_warn)
            if(!array_key_exists($inputName,$_POST))
                self::warn($inputName);
         // Get value from post value form method
        $inputName = $_POST[$inputName];
        parent::fill_text($inputName);
    	return $inputName;
    }
    public static function date($inputName, $format = 'Y-m-d', $_warn = true)
    {
        $inputData = self::request($inputName, $_warn);
        $datetime = date($format, strtotime($inputData));
        return $datetime;
    }
    public static function request($inputName, $validation = NULL)
    {
        $inputValue = $_POST[$inputName];
        parent::fill_text($inputValue);
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
        // Check is Input Key able
        if($errWarning)
            if(!array_key_exists($inputName,$_GET))
                self::warn($inputName);
        // Get value from get value form method
    	$inputName = $_GET[$inputName];
        parent::fill_text($inputName);
        return $inputName;
    }
    public static function header($inputName, $errWarning = true)
    {
        $header = getallheaders();
        if($errWarning)
            if(!array_key_exists($inputName,$header))
                self::warn($inputName);
        // Get value from get value form method
        $inputName = $header[$inputName];
        parent::fill_text($inputName);
        return $inputName;
    }
}
