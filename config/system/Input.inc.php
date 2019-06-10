<?php
class Input extends dsSystem
{
    function __construct()
    {
        
    }
    
    public static function post($__nm)
    {
        // Check is Input Key able
        if(!array_key_exists($__nm,$_POST)){
            parent::MessageError(uri(0).'/'.uri(1),'Input Name : '. $__nm .' not found!');
        }
         // Get value from post value form method
        $__nm = $_POST[$__nm];
        $__nm = parent::fill_text($__nm);
    	return $__nm;
    }

    public static function request($__nm)
    {
        // Check is Input Key able
        if(!array_key_exists($__nm,$_POST)){
            parent::MessageError(uri(0).'/'.uri(1),'Input Name : '. $__nm .' not found!');
        }
         // Get value from post value form method
        $__nm = $_REQUEST[$__nm];
        $__nm = parent::fill_text($__nm);
    	return $__nm;
    }

    public static function get($__nm)
    { // Get value from get value form method
    	$__nm = $_GET[$__nm];
        $__nm = parent::fill_text($__nm);
        return $__nm;
    }

    public static function header($__nm)
    {
        $header = getallheaders();
        // Get value from get value form method
        $__nm = $header[$__nm];
        $__nm = parent::fill_text($__nm);
        return $__nm;
    }
}
