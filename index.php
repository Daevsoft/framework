<?php

// this file used when all folder is in the public_html folder.

define('root', TRUE);
// Empty String
define('STRING_EMPTY', '');


require_once __DIR__.'/config/system/ds/Keys.inc.php';
require_once __DIR__.'/config/system/ds/Indexes.inc.php';
Indexes::init();

// Get global variable
require_once Indexes::$DIR_APP.'constants/define' . Key::EXT_PHP;
// Start the session
session_start();

// Include All required files for Core.php
require_once Indexes::$DIR_ROOT.'config/system/autoload'. Key::EXT_PHP;
_autoload2f40af1f10ad60c89a4b333ee7943d49::getLoader();

// Set Core as Null Value
$core = NULL;
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
// Connect Web from Core Object to running All Process
$core->connect();