<?php
class API extends dsSystem
{
    public static $tempRecordApi;
    public static $requestLink;
    private static $sql;

    public function __construct()
    {
    }
    private static function apiRequestReceiver($_reqSeed, $_funcResponse, $_data)
    {
        if(is_null(API::$sql))
            API::$sql = new dsModel();
        $seed = dsSystem::fill_text($_reqSeed);
        $request_not_found = true;
        if(isset(self::$tempRecordApi[self::$requestLink][$seed])){
            if(self::$tempRecordApi[self::$requestLink][$seed]){
                $response = $_funcResponse($_data, API::$sql);
                $request_not_found = false;
                if(is_array($response))
                    echo json_encode($response);
                if(is_string($response))
                    echo json_encode([$response]);
                die();
            }
        }
        if($request_not_found){
            parent::MessageError('Api <b>'.$seed.'</b> not found !');
        }
    }
    public static function post($_reqSeed, $_funcResponse)
    {
        self::apiRequestReceiver($_reqSeed, $_funcResponse, $_POST);
    }
    public static function get($_reqSeed, $_funcResponse)
    {
        self::apiRequestReceiver($_reqSeed, $_funcResponse, $_GET);
    }
    public static function request($_reqSeed, $_funcResponse)
    {
        self::apiRequestReceiver($_reqSeed, $_funcResponse, $_REQUEST);
    }
    public static function header($_reqSeed, $_funcResponse)
    {
        self::apiRequestReceiver($_reqSeed, $_funcResponse, headers_list());
    }
    public static function register($_reqApi)
    {
        self::$requestLink = $_reqApi;
        if(!isset(self::$tempRecordApi[$_reqApi])){
            self::$tempRecordApi[$_reqApi] = NULL;
        }
    }
    public static function route($_reqSeed, $_seed)
    {
        if($_seed == Key::INDEX)
            $_seed = Key::CHAR_SLASH;
        $seed = parent::fill_text($_seed);
        $reqSeed = parent::fill_text($_reqSeed);
        self::$requestLink = $reqSeed;
        self::$tempRecordApi[self::$requestLink][$seed] = TRUE;
    }

    public static function getTable($__q_or_t, $__wh = STRING_EMPTY, $__bool = 'AND',  $__ord = "ASC")
    {
        return BackEnd::table($__q_or_t, $__wh, $__bool, $__ord)::fetch_all();
    }

    public static function getRow($__q_or_t, $__wh = "", $__bool = "AND")
    {
        return BackEnd::row($__q_or_t, $__wh, $__bool)::fetch_row();
    }

    public static function select($__q_or_t, $__wh = STRING_EMPTY, $__bool = 'AND')
    {
        return BackEnd::table($__q_or_t, $__wh, $__bool);
    }
}
