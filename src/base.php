<?php

namespace Ds;


function isSlash()
{
    return strstr(__DIR__, '/') != false;
}

function replaceSlash($text)
{
    if (isSlash()) {
        return str_ireplace('\\', '/', $text);
    } else {
        return $text;
    }
}

define('STRING_EMPTY', '');
if(!defined('SLASH')){
    define('SLASH', isSlash() ? '/' : '\\');
}
define("ROOT", dirname(__DIR__, 4) . SLASH);

abstract class AppIndex
{
    public static $SERVER_PROTOCOL;
    public static $HTTP_HOST;
    public static $BASE_URL;
    public static $BASE_ASSETS;
    public static $LINK_FILES;
    static function init($swooleRequest = null)
    {
        require_once __DIR__ . '/Helper/Function.php';

        // Ambil Host dari Swoole Header atau Global $_SERVER
        if ($swooleRequest) {
            // Di Swoole, HTTP_HOST ada di dalam array $request->header
            self::$HTTP_HOST = $swooleRequest->header['host'] ?? 'localhost';
            
            // Cek Protokol (http atau https)
            $isHttps = ($swooleRequest->server['https'] ?? 'off') !== 'off' || ($swooleRequest->header['x-forwarded-proto'] ?? '') === 'https';
            self::$SERVER_PROTOCOL = $isHttps ? 'https' : 'http';
        } else {
            // Fallback untuk PHP-FPM / Apache
            self::$HTTP_HOST = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::$SERVER_PROTOCOL = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }

        self::$BASE_URL = self::$SERVER_PROTOCOL . '://' . self::$HTTP_HOST;
        self::$BASE_ASSETS = self::$BASE_URL . '/assets/';
        self::$LINK_FILES = self::$BASE_URL . '/files/';
    }
}
abstract class Dir
{
    static string $MAIN;
    static string $APP;
    static string $ROUTE;
    static string $CONTROLLERS;
    static string $MODELS;
    static string $HELPERS;
    static string $VIEWS;
    static string $JOBS;
    static string $MIDDLEWARES;
    static string $PROVIDERS;
    static string $DB;
    static string $MIGRATIONS;
    static string $SQLITE;
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
        self::$APP = self::$MAIN . 'app' . SLASH;
        self::$DB = self::$MAIN . 'database' . SLASH;
        self::$MIGRATIONS = self::$DB . 'migrations' . SLASH;
        self::$ROUTE = self::$APP . 'Route' . SLASH;
        self::$CONTROLLERS = self::$APP . 'Controllers' . SLASH;
        self::$MODELS = self::$APP . 'Models' . SLASH;
        self::$HELPERS = self::$APP . 'Helpers' . SLASH;
        self::$VIEWS = self::$APP . 'Views' . SLASH;
        self::$MIDDLEWARES = self::$APP . 'Middlewares' . SLASH;
        self::$STORAGE = self::$MAIN . 'storage' . SLASH;
        self::$JOBS = self::$MAIN . 'cronjob' . SLASH;
        self::$TRASH = self::$STORAGE . 'trash' . SLASH;
        self::$CACHE = self::$STORAGE . 'cache' . SLASH;
        self::$CACHE_VIEW = self::$CACHE . 'views' . SLASH;
        self::$CONFIG_TEMP = self::$CACHE . 'config.temp.php';
        self::$VENDOR = self::$MAIN . 'vendor' . SLASH;
        self::$CACHE_TIME = self::$STORAGE . 'cache' . SLASH . 'times' . SLASH . 'temp';
        self::$SQLITE = self::$DB . 'database.sqlite';
    }
}
spl_autoload_register(function ($name) {
    $vendorPos = strpos($name, '\\');
    $namespace = substr($name, 0, $vendorPos);
    // check if $name is from DS namespace
    if ($namespace == 'Ds') {
        $name = substr($name, $vendorPos);
        $name = __DIR__ . $name;
        $filename = replaceSlash($name);
        require_once $filename . '.php';
    } else if ($namespace == 'App') {
        $name = substr($name, strlen('App') + 1);
        $filename = Dir::$APP . $name;
        $filename = replaceSlash($filename);
        require_once $filename . '.php';
    }
});
