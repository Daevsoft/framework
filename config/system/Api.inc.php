<?php
class Api extends dsSystem
{
    public static $OPENED = false;

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
        $run = $_initCallback(Input::all(), self::$sql);
        if(is_bool($run))
            if(!$run)
                if($_failureCallback != NULL){
                    $_failureCallback();
                    die();
                }else
                    die('Access is denied!');
    }
    private static function apiRequestReceiver($_reqSeed, $_funcResponse, $_data)
    {
        Api::$OPENED = true;

        self::setSql();
        $request_not_found = true;
        if(isset(self::$tempRecordApi[self::$requestLink][self::$requestMtd])){
            if(self::$requestMtd == $_reqSeed){
                $response = $_funcResponse($_data, API::$sql);
                $request_not_found = false;
                header('Content-Type: application/json');
                if(is_array($response) || is_object($response)){
                    echo json_encode($response);
                }else
                    if(!is_null($response)){
                        echo $response;
                    }
                die();
            }
        }else if($request_not_found){
            header('Content-Type: application/json');
            echo json_encode(['response' => 'Api '.self::$requestLink.Key::CHAR_SLASH.self::$requestMtd.' not found !']);
            die();
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
    public static function json($_reqSeed, $_funcResponse)
    {
        self::apiRequestReceiver($_reqSeed, $_funcResponse, json_decode(file_get_contents('php://input')));
    }
    public static function register($_reqApi)
    {
        self::$requestLink = strtolower($_reqApi);
        if(!isset(self::$tempRecordApi[$_reqApi])){
            self::$tempRecordApi[$_reqApi] = [];
        }
    }
    public static function route($_reqApiClass, $_reqMtd)
    {
        if($_reqMtd == Key::INDEX)
            $_reqMtd = Key::CHAR_SLASH;
        parent::fill_text($_reqMtd);
        parent::fill_text($_reqApiClass);
        self::$requestLink = strtolower($_reqApiClass);
        self::$requestMtd = $_reqMtd;
        self::$tempRecordApi[self::$requestLink][$_reqMtd] = TRUE;
    }
}
