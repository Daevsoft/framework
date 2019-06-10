<?php
secure_page();
class FrontEnd
{
  /**
  * Class for View and Controller
  */
  private static $_lang_php;
  private static $_lang_json;

  // $_seed => string : ind,end
  // $_type => string : php, json
  public static function set_lang($_seed, $_type = STRING_EMPTY)
  {
    $_type = empty_or_value($_type, config('language_type'));
    switch ($_type) {
      case 'json':
        if (is_null(self::$_lang_json)) {
          $json = file_get_contents(Indexes::$DIR_APP.'lang/resource/message.json');
          self::$_lang_json = json_decode($json);
        }
        break;
      
      default:
        if (is_null(self::$_lang_php)) {
          self::$_lang_php = require_once(Indexes::$DIR_APP.'lang/message/'.$_seed.'/message.php');
        }
        break;
    }
  }
  public static function get_lang($_seed, $_type = STRING_EMPTY)
  {
    $_type = empty_or_value($_type, config('language_type'));
    if ($_type == 'json') {
      // return json lang
      if (!is_null(self::$_lang_json)) {
        return self::$_lang_json;
      }
    }else{
      // return php lang
      if (!is_null(self::$_lang_php)) {
        return self::$_lang_php;
      }
    }
  }

  public function __construct()
  {
  }
  public function view($routeList, $renameController, $path)
  {
    $path = (array_key_exists($path,$routeList)) ? $routeList[$path] : $path;
    $path = !string_contains('/',$path) ? $path.Key::CHAR_SLASH.Key::INDEX : $path;
    $requestTarget = explode('/',$path);
    // Get structure app
    $structure_app = config('structure_app');
    // Get first load thing
    $first_load = config('first_load');
    // if first load not include index uri then add index
    if(count($requestTarget) == 1)
      $requestTarget[1] = Key::INDEX;
    // Get action based on structure
    if($structure_app == MVC
      || ($requestTarget[0] != API && $structure_app == MULTI)){
        
      $controller = $requestTarget;
      // Get the first Controller will loaded
      $controller[0] = string_empty($controller[0]) ?
        $first_load : (string_contains('/', $controller[0]) ?
        substr($controller[0], 0, strpos($controller[0], '/')) : $controller[0]);
      // rename controller
      $controller[0] = (array_key_exists($controller[0],$renameController)) ? 
      // Rename controller is able ?
      $renameController[$controller[0]] : $controller[0];
      $controller[0] .= Key::CONTROLLER;
      // end rename controller
      $obj = new $controller[0](); // Create object controller for check parentController has been extended
      if (dsCore::$_access) { // Load Access if _access is TRUE
        if (count($controller) <= 2) { // Get Uri From Address request
          $con = $controller[1]; // Get Function Name from Controller
          $__params = array_slice($controller, 2,count($controller)); // Get Argument of function in classController
          if ($__params == array()) { // Is Array 0 index or not
            $obj->$con(); // Execute Function in Controller
          }else{
            $obj->$con($__params);
          }
        }else {
          //call_user_method_array(array_slice($controller, 0,2), $obj, array_slice($controller, 2,count($controller)));
          call_user_func_array(
            [$obj, $controller[1]], // Controller class & method name
            array_slice($controller, 2,count($controller)) // parameter value
          );
        }
      }else{
        die("dsController not extended in this Controller or contructor not called.");
      }
    }else{
      $iRouteStep = ($structure_app == MULTI ? 1 : 0);
      $apiRequest = dsSystem::fill_text($requestTarget[0 + $iRouteStep]);
      $apiRequest = $apiRequest == STRING_EMPTY ? $first_load : $apiRequest;
      $filename = Indexes::$DIR_API.$apiRequest.'.php';
      $apiTarget = count($requestTarget) > 1 + $iRouteStep ? $requestTarget[1 + $iRouteStep] : Key::CHAR_SLASH;
      $indexOf = strpos($apiTarget,'?');
      $apiRoute = $indexOf != 0 ? substr($apiTarget, 0, $indexOf) : $apiTarget;
      API::route($apiRequest, $apiRoute);
      if(file_exists($filename))
        require_once $filename;
      else
        $ex = new dsException(new Exception("$filename file is not found or not registered!"),'URI Address -> '. $path,FALSE);
    }
  }
  public static function route($_routeTarget, $_routeName)
  {
    Route::set_route(site($_routeTarget), $_routeName);
  }
  static function page($__fl='',$__dt = array())
  {
    // Create your security filter here
    Page::__page($__fl, $__dt); // Load file view
  }
}
