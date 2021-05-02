<?php

define('root', TRUE);
// Empty String
define('STRING_EMPTY', '');
require_once dirname(__DIR__).'/config/system/ds/Keys.inc.php';
require_once dirname(__DIR__).'/config/system/ds/Indexes.inc.php';
Indexes::init();

// Get global variable
require Indexes::$DIR_APP.'constants/define' . Key::EXT_PHP;

// Include All required files for Core.php
require '../config/system/autoload'. Key::EXT_PHP;
_autoload2f40af1f10ad60c89a4b333ee7943d49::getLoader();
// Get Byte of Core Object from cache
$core_cache = file_get_contents(Indexes::$DIR_CACHE_OBJECT);
// Unserialize Core object from cache
$core = unserialize($core_cache);
// If Core is never cached then reinitialize Core Object
if(!is_object($core) && config('status') == Key::DEVELOPMENT){
   $core = new dsCore();
   $ref = serialize($core);
   file_put_contents(Indexes::$DIR_CACHE_OBJECT, $ref);
}


ini_set('session.save_handler', 'files');
$key = 'secret_string';
$handler = new DsSessionHandler(dirname(__DIR__).'/storage/session/', $key);  
session_set_save_handler($handler, true);
DsSessionHandler::$handler = $handler;
// Start the session
session_start();
unset($handler);
unset($_SESSION);

// Connect Web from Core Object to running All Process
$core->connect();
