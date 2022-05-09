<?php

define('root', TRUE);
// Empty String
define('STRING_EMPTY', '');
$root = dirname(__DIR__);
require_once $root.'/config/system/ds/Keys.inc.php';
require_once $root.'/config/system/ds/Indexes.inc.php';
Indexes::init();

// Get global variable
require Indexes::$DIR_APP.'constants/define' . Key::EXT_PHP;

// Include All required files for Core.php
require $root.'/config/system/autoload'. Key::EXT_PHP;
_autoload2f40af1f10ad60c89a4b333ee7943d49::getLoader();

// If Core is never cached then reinitialize Core Object
$core = new dsCore();

// Connect Web from Core Object to running All Process
$core->connect();