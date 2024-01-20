<?php

namespace Ds;

use Ds\Foundations\Common\Func;
use Ds\Foundations\Config\AppEnv;

define('STRING_EMPTY', '');
define("ROOT", dirname(__DIR__, 3).'\\');

abstract class AppIndex {
    public static $SERVER_PROTOCOL;
    public static $HTTP_HOST;
    public static $BASE_URL;
    public static $BASE_ASSETS;
    public static $LINK_FILES;

    static function init(){
        require_once __DIR__.'/helper/Function.php';
        // your web server host (ex:localhost/index.php)
        self::$HTTP_HOST = $_SERVER['HTTP_HOST'];
        // Base url
        self::$BASE_URL = self::$SERVER_PROTOCOL.'://'.self::$HTTP_HOST;
        // Assets folder
        self::$BASE_ASSETS = self::$BASE_URL.'/assets/';
        // Asset files url
        self::$LINK_FILES = self::$BASE_URL. '/files/';
    }
}
abstract class Dir
{
    static string $MAIN;
    static string $APP;
    static string $ROUTE;
    static string $CONTROLLERS;
    static string $MODELS;
    static string $VIEWS;
    static string $MIDDLEWARES;
    static string $PROVIDERS;
    static string $STORAGE;
    static string $CACHE;
    static string $CONFIG_TEMP;
    static string $CACHE_VIEW;
    static string $CACHE_TIME;
    static string $VENDOR;
    static string $TRASH;

    static function init()
    {
        self::$MAIN = ROOT;
        self::$APP = self::$MAIN . 'app/';
        self::$ROUTE = self::$APP . 'route/';
        self::$CONTROLLERS = self::$APP . 'controllers/';
        self::$MODELS = self::$APP . 'models/';
        self::$VIEWS = self::$APP . 'views/';
        self::$MIDDLEWARES = self::$APP . 'middleware/';
        self::$STORAGE = self::$MAIN . 'storage/';
        self::$TRASH = self::$STORAGE . 'trash/';
        self::$CACHE = self::$STORAGE . 'cache/';
        self::$CACHE_VIEW = self::$CACHE.'views/';
        self::$CONFIG_TEMP = self::$CACHE . 'config.temp.php';
        self::$VENDOR = self::$MAIN. 'vendor/';
        self::$CACHE_TIME = self::$STORAGE.'cache/times/temp';
        include_once self::$CONFIG_TEMP;
    }
}

spl_autoload_register(function ($name) {
    $namespace = substr($name, 0, strpos($name, '\\'));
    // check if $name is from DS namespace
    if ($namespace == 'Ds') {
        require_once dirname(__DIR__) . '\\' . $name . '.php';
    } else if($namespace == 'App'){
        require_once ROOT . $name . '.php';
    }
});
