<?php
class Api extends dsSystem
{
    public static $tempRecordApi;
    public static $requestLink;
    public static $requestMtd;
    private static $sql;

    public function __construct()
    {
    }
    private static function setSql()
    {
        if(is_null(API::$sql))
            API::$sql = new dsModel();
    }
    public static function init($_initCallback, $_failureCallback = NULL){
        self::setSql();
        $run = $_initCallback($_REQUEST, self::$sql);
        if(is_bool($run))
            if(!$run)
                if($_failureCallback != NULL){
                    $_failureCallback();
                    die();
                }else{
                    die('Access is denied!');
                }
    }
    private static function apiRequestReceiver($_reqSeed, $_funcResponse, $_data)
    {
        self::setSql();
        $request_not_found = true;
        if(isset(self::$tempRecordApi[self::$requestLink][self::$requestMtd])){
            if(self::$requestMtd == $_reqSeed){
                $response = $_funcResponse($_data, API::$sql);
                $request_not_found = false;
                if(is_array($response))
                    echo json_encode($response);
                else
                    if(!is_null($response))
                        echo json_encode([$response]);
                die();
            }
        }else if($request_not_found){
            parent::MessageError('Api <b>'.self::$requestLink.'</b> not found !');
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
    public static function route($_reqApiClass, $_reqMtd)
    {
        if($_reqMtd == Key::INDEX)
            $_reqMtd = Key::CHAR_SLASH;
        parent::fill_text($_reqMtd);
        parent::fill_text($_reqApiClass);
        self::$requestLink = $_reqApiClass;
        self::$requestMtd = $_reqMtd;
        self::$tempRecordApi[self::$requestLink][$_reqMtd] = TRUE;
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
