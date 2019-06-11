<?php
class Input extends dsSystem
{
    function __construct()
    {
        
    }
    private static function warn($__nm){
        parent::MessageError(uri(0).'/'.uri(1),'Input Name : '. $__nm .' not found!');
    }
    public static function post($__nm, $_warn = true)
    {
        // Check is Input Key able
        if($_warn)
            if(!array_key_exists($__nm,$_POST))
                self::warn($__nm);
         // Get value from post value form method
        $__nm = $_POST[$__nm];
        $__nm = parent::fill_text($__nm);
    	return $__nm;
    }

    public static function request($__nm, $_warn = true)
    {
        // Check is Input Key able
        if($_warn)
            if(!array_key_exists($__nm,$_REQUEST))
                self::warn($__nm);
         // Get value from post value form method
        $__nm = $_REQUEST[$__nm];
        $__nm = parent::fill_text($__nm);
    	return $__nm;
    }
    public static function get($__nm, $_warn = true)
    {
        // Check is Input Key able
        if($_warn)
            if(!array_key_exists($__nm,$_GET))
                self::warn($__nm);
        // Get value from get value form method
    	$__nm = $_GET[$__nm];
        $__nm = parent::fill_text($__nm);
        return $__nm;
    }

    public static function header($__nm, $_warn = true)
    {
        $header = getallheaders();
        if($_warn)
            if(!array_key_exists($__nm,$header))
                self::warn($__nm);
        // Get value from get value form method
        $__nm = $header[$__nm];
        $__nm = parent::fill_text($__nm);
        return $__nm;
    }
}
