<?php
class Api extends dsSystem
{
    public static $OPENED = false;

    public static $tempRecordApi;
    public static $requestLink;
    public static $requestMtd;
    private static $sql;
    private static $isApiExist = TRUE;

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
    public static function runApi($requestMethod){
        self::$requestMtd = $requestMethod;
        self::apiRequestReceiver();
    }
    private static function apiRequestReceiver()
    {
        Api::$OPENED = true;

        self::setSql();
        self::$isApiExist = true;
        if(isset(self::$tempRecordApi[self::$requestLink][self::$requestMtd])){
            if(self::$requestMtd){

                if(isset(self::$tempRecordApi[self::$requestLink]) &&
                    isset(self::$tempRecordApi[self::$requestLink][self::$requestMtd])){
                        $api = self::$tempRecordApi[self::$requestLink][self::$requestMtd];
                        [ $_funcResponse, $_data ] = $api;
                        
                        $response = $_funcResponse($_data, API::$sql);
                        header('Content-Type: application/json');
                        if(is_array($response) || is_object($response)){
                            echo json_encode($response);
                            die();
                        }else
                            if(!is_null($response)){
                                echo $response;
                                die();
                            }
                }else{ self::$isApiExist = false; }
            }
            if(!self::$isApiExist){
                // API not found
                die('Api not found');
            }
        }else if(self::$isApiExist){
            header('Content-Type: application/json');
            echo json_encode(['response' => 'Api '.self::$requestLink.Key::CHAR_SLASH.self::$requestMtd.' not found !']);
            die();
        }
    }
    public static function post($_reqSeed, $_funcResponse)
    {
        self::route(self::$requestLink, $_reqSeed, $_funcResponse, $_POST);
        // self::apiRequestReceiver($_reqSeed, $_funcResponse, $_POST);
    }
    public static function get($_reqSeed, $_funcResponse)
    {
        self::route(self::$requestLink, $_reqSeed, $_funcResponse, $_GET);
        // self::apiRequestReceiver($_reqSeed, $_funcResponse, $_GET);
    }
    public static function request($_reqSeed, $_funcResponse)
    {
        self::route(self::$requestLink, $_reqSeed, $_funcResponse, $_REQUEST);
        // self::apiRequestReceiver($_reqSeed, $_funcResponse, $_REQUEST);
    }
    public static function header($_reqSeed, $_funcResponse)
    {
        self::route(self::$requestLink, $_reqSeed, $_funcResponse, headers_list());
        // self::apiRequestReceiver($_reqSeed, $_funcResponse, headers_list());
    }
    public static function json($_reqSeed, $_funcResponse)
    {
        self::route(self::$requestLink, $_reqSeed, $_funcResponse, json_decode(file_get_contents('php://input')));
    }
    public static function register($_reqApi)
    {
        self::$requestLink = strtolower($_reqApi);
        if(!isset(self::$tempRecordApi[$_reqApi])){
            self::$tempRecordApi[$_reqApi] = [];
        }
    }
    public static function route($_reqApiClass, $_reqMtd, $_funcResponse, $data)
    {
        if($_reqMtd == Key::INDEX)
            $_reqMtd = Key::CHAR_SLASH;
        parent::fill_text($_reqMtd);
        parent::fill_text($_reqApiClass);
        self::$requestLink = strtolower($_reqApiClass);
        if($_reqApiClass == self::$requestLink && self::$requestMtd == $_reqMtd){
            self::$tempRecordApi[self::$requestLink][$_reqMtd] = [$_funcResponse, $data ];
        }
    }
}
