<?php
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
    try{
      if(is_string($command))
        exec($command);
    }catch(Exeption $ex){
    }
  }
  public static function string_contains($string, $contain)
  {
		if(strpos(strtolower($string), strtolower($contain)) === false)
			return false;
		else
			return true;
  }
}