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
    public static function file($name)
    {
        $data = $_FILES[$name];
        $data['name'] = 'F'.date('dmyhis').$data['name'];
        $data['ext'] = strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
        return $data;
    }
    public static function upload($inputFile, $format = [], $target_dir = 'main/assets/img/', $replace_dir = false, $maxSize = 2000000)
    {
        $target_file = (!$replace_dir ? Indexes::$DIR_ROOT : '').$target_dir.basename($inputFile['name']);
        // test($target_file);
        $uploadOK = true;
        $msg = STRING_EMPTY;
        // Check if file already exists
        // if (file_exists($target_file)) {
        //     echo "Sorry, file already exists.";
        //     $uploadOK = false;
        // }
        
        // Check file size
        if ($inputFile["size"] > $maxSize) {
            $msg .= "Sorry, your file is too large.<br>";
            $uploadOK = false;
        }
        
        // Allow certain file formats
        if (count($format) > 0){
            $find = in_array($inputFile['ext'], $format);
            if(!$find) {
                $msg .= "Sorry, only ";
                foreach ($format as $type) {
                    $msg .= $type.' ';
                }
                $msg .= " files are allowed.<br>";
                $uploadOK = false;
            }
        }
        
        
        // Check if $uploadOk is set to 0 by an error
        if (!$uploadOK) {
            set_error($msg."Sorry, your file was not uploaded.");
            return $uploadOK;
        // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($inputFile["tmp_name"], $target_file)) {
                return true;
            } else {
                return false;
            }
        }
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
        $input = isset($_POST) ? $_POST : $_GET;
        if (isset($_POST) || isset($_GET)) {
            foreach ($input as $key => $v) {
                $data[$key] = Input::request($key);
            }
        }
        return $data;
    }
}
