<?php
class Input extends dsSystem
{
    function __construct()
    {
        
    }
    private static function warn($inputName){
        parent::MessageError(uri(0).Key::CHAR_SLASH.uri(1),'Input Name : '. $inputName .' not found!');
    }
    public static function post($inputName, $_warn = true)
    {
        // Check is Input Key able
        if($_warn)
            if(!array_key_exists($inputName,$_POST))
                self::warn($inputName);
         // Get value from post value form method
        $inputName = $_POST[$inputName];
        $inputName = parent::fill_text($inputName);
    	return $inputName;
    }

    public static function date($inputName, $format = 'Y-m-d', $_warn = true)
    {
        $inputData = self::request($inputName, $_warn);
        $datetime = date($format, strtotime($inputData));
        return $datetime;
    }

    public static function request($inputName, $_warn = true)
    {
        // Check is Input Key able
        if($_warn)
            if(!array_key_exists($inputName,$_REQUEST))
                self::warn($inputName);
         // Get value from post value form method
        $inputName = $_REQUEST[$inputName];
        $inputName = parent::fill_text($inputName);
    	return $inputName;
    }
    public static function get($inputName, $errWarning = true)
    {
        // Check is Input Key able
        if($errWarning)
            if(!array_key_exists($inputName,$_GET))
                self::warn($inputName);
        // Get value from get value form method
    	$inputName = $_GET[$inputName];
        $inputName = parent::fill_text($inputName);
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
        $inputName = parent::fill_text($inputName);
        return $inputName;
    }
}
