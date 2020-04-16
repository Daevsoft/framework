<?php

define('ZERO_ARG',0);
define('FIRST_ARG',1);
define('SECOND_ARG',2);
define('THIRD_ARG',3);
define('FOURTH_ARG',4);
define('FIFTH_ARG',5);

define(STRING_EMPTY, '');
define('CONTROLLERS','/controllers');
define('MODELS','/models');
define('VIEWS','/views');
define('APIS','/api');
define('CONTROLLER','Controller');
define('MODEL','Model');
define('VIEW','view');
define('API','Api');

define('COMMAND_ADD','add');
define('COMMAND_DEL','delete');
define('COMMAND_RESTORE','restore');
define('COMMAND_API','api');
define('COMMAND_VIEW','view');
define('COMMAND_CONTROLLER','controller');
define('COMMAND_MODEL','model');
define('RESPONSE','DsResponse: ');

define('MAIN_DIR', dirname(dirname(dirname(__DIR__))));
define('CONFIG_DIR',MAIN_DIR.'/config');
define('SYSTEM_DIR',CONFIG_DIR.'/system');
define('STORAGE_DIR',MAIN_DIR.'/storage');
define('TRASH_DIR',STORAGE_DIR.'/trash');

function msg($_msg){
  echo RESPONSE.$_msg."\n";
}

function get_command($_pos = '')
{
  if($_pos == STRING_EMPTY)
    return $GLOBALS['argv'];
  else
    return $GLOBALS['argv'][$_pos];
}
function read($_msg){
  $r = readline($_msg);
  return $r;
}
// When want to added overwrite function for all object class
class Ds
{
  public function __construct() {
  }
  public function Start()
  {
    $_autoload = require __DIR__.'/Autoload.inc.php';
    foreach ($_autoload as $file) {
      // Load all command executor
      require $file.'.inc.php';
    }
    $this->init();
  }
  public function init()
  {
    // Create Executor
    $executor = new Executor();
    $executor->Commands(get_command());
  }
  public function GetArgValue($_Arg)
  {
    // get value command like 'make:controller' get value at 'controller' only
    return explode(':',$_Arg)[FIRST_ARG];
  }
  public function IsContain($_string_text, $_find)
  {
    return (strstr($_string_text, $_find) != '');
  }
  public static function force_cmd($command)
  {
    if(is_string($command))
      exec($command);
    else
      msg('Error :'.$command.' wrong command');
  }
  public static function string_contains($string, $contain)
  {
		if(strpos(strtolower($string), strtolower($contain)) === false)
			return false;
		else
			return true;
  }
}