<?php

class Indexes{
    public static $DIR_ROOT;
    public static $SERVER_PROTOCOL;
    public static $DIR_APP;
    public static $DIR_CONFIG;
    public static $DIR_SYSTEM;
    public static $DIR_MODULES;
    public static $DIR_EVENTS;
    public static $DIR_CACHE;
    public static $DIR_API;
    public static $DIR_STORAGE;
    public static $DIR_CACHE_TIME;
    public static $DIR_CACHE_VIEW;
    public static $DIR_CACHE_OBJECT;
    public static $DIR_VIEWS;
    public static $HTTP_HOST;
    public static $BASE_URL;
    public static $BASE_ASSETS;
    public static $LINK_FILES;

    public static function init(){
        $root_directory = dirname(dirname(dirname(__DIR__))).Key::CHAR_SLASH;
        self::$DIR_ROOT = $root_directory;//($root_dir ? dirname(dirname(__DIR__)) : ). Key::CHAR_SLASH;
        self::$SERVER_PROTOCOL = strtolower(explode(Key::CHAR_SLASH,
            isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : $_SERVER['SERVER_PROTOCOL']
        )[0]);
        // App Directory
        self::$DIR_APP = self::$DIR_ROOT. Key::D_APP;
        // Configuration Directory
        self::$DIR_CONFIG = self::$DIR_ROOT. Key::D_CONFIG;
        // Storage Directory for Cache location
        self::$DIR_STORAGE = self::$DIR_ROOT. Key::D_STORAGE;
        // System Directory
        self::$DIR_SYSTEM = self::$DIR_CONFIG. Key::CHAR_SLASH. Key::D_SYSTEM;
        // Modules Directory
        self::$DIR_MODULES = self::$DIR_APP. Key::MODULES. Key::CHAR_SLASH;
        // Events Directory
        self::$DIR_EVENTS = self::$DIR_APP. Key::EVENTS. Key::CHAR_SLASH;
        // Cache Directory
        self::$DIR_CACHE = self::$DIR_APP. Key::CACHE. Key::CHAR_SLASH;
        // API Directory
        self::$DIR_API = self::$DIR_APP.'api'.Key::CHAR_SLASH;
        // Cache Time Directory
        self::$DIR_CACHE_TIME = self::$DIR_STORAGE.'cache/times/temp';
        // Cache View Directory
        self::$DIR_CACHE_VIEW = self::$DIR_STORAGE.'cache/views/';
        // Cache Object Directory
        self::$DIR_CACHE_OBJECT = self::$DIR_STORAGE.'cache/object/ref';
        // View Directory
        self::$DIR_VIEWS = self::$DIR_APP.'views'.Key::CHAR_SLASH;
        // your web server host (ex:localhost/index.php)
        self::$HTTP_HOST = $_SERVER['HTTP_HOST'];
        // Base url
        self::$BASE_URL = self::$SERVER_PROTOCOL.'://'.self::$HTTP_HOST;
        // Assets folder
        self::$BASE_ASSETS = self::$BASE_URL.Key::CHAR_SLASH.'assets/';
        // Asset files url
        self::$LINK_FILES = self::$BASE_URL. Key::D_STORAGE_FILES;
    }
}