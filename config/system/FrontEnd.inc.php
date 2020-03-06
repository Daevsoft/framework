<?php
secure_page();
class FrontEnd extends dsCore
{
  /**
  * Class for View and Controller
  */
  private static $_lang_php;
  private static $_lang_json;

  public function __construct()
  {
		// Set Default Controller First Load
		$req_uri = (!isset($_SERVER['REQUEST_URI'])) ?
		config('first_load') : trim($_SERVER['REQUEST_URI'],Key::CHAR_SLASH);
		$this->view($GLOBALS['routeList'],$GLOBALS['renameController'], $req_uri);
  }

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
          self::$_lang_php = require_once(Indexes::$DIR_APP.'lang/message'.Key::CHAR_SLASH.$_seed.'/message.php');
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

  private function get_path_validate($path,Array $routeList)
  {
    // remove GET request data
    $path_fix = $path = string_crop($path, 0, '?');
    $check_path_exist_renamed = string_crop($path, 0, Key::CHAR_SLASH);
    
    $path_route_check = array_search($check_path_exist_renamed, $routeList);
    $path_fix = ($path_route_check) ? $path_route_check : $path;
    if($path_route_check && string_contains(Key::CHAR_SLASH,$path)){
      $path_fix .= string_crop($path, strpos($path, Key::CHAR_SLASH));
    }
    if($path_fix){
      $path = $path_fix;
    }
    $path = !string_contains(Key::CHAR_SLASH,$path) ? $path.Key::CHAR_SLASH.Key::INDEX : $path;
    $requestTarget = explode(Key::CHAR_SLASH,$path);
    return $requestTarget;
  }
  public function view(Array $routeList,Array $renameController, $path)
  {
    $requestTarget = $this->get_path_validate($path, $routeList);
    // Get structure app
    $structure_app = config('structure_app');
    // Get first load thing
    $first_load = config('first_load');
    // if first load not include index uri then add index
    if(count($requestTarget) == 1)
      $requestTarget[1] = Key::INDEX;
    // Get action based on structure
    if($structure_app == Key::MVC
      || ($requestTarget[0] != Key::API && $structure_app == Key::MULTI)){

      $controller = $requestTarget;
      // Get the first Controller will loaded
      $controller[0] = string_empty($controller[0]) ?
        $first_load : (string_contains(Key::CHAR_SLASH, $controller[0]) ?
        substr($controller[0], 0, strpos($controller[0], Key::CHAR_SLASH)) : $controller[0]);
      // find rute value and get key
      $route_found = array_search($controller[0],$renameController);
      // rename controller
      $controller[0] = $route_found ? 
      // Rename controller is able ?
      $route_found : $controller[0];
      $controller[0] .= Key::CONTROLLER;
      // get object name
      $object_name = $controller[0];
      // end rename controller
      $obj = new $object_name(); // Create object controller for check parentController has been extended
      
      if ($obj->_access) { // Load Access if dsCore->_access is TRUE
        if (count($controller) <= 2) { // Get Uri From Address request
          $_function_name = $controller[1]; // Get Function Name from Controller
          $__params = array_slice($controller, 2,count($controller)); // Get Argument of function in classController
          // Check the function existing
          if(method_exists($obj, $_function_name)){
            if ($__params == array()) { // Is Array 0 index or not
              $obj->$_function_name(); // Execute Function in Controller
            }else{
              $obj->$_function_name($__params);
            }
          }else{
            throw new dsException(new Exception(
              'unexpected function name <i>\''.
              $_function_name.'\'</i> in object <i>\''.
              $object_name.'\'</i>. Function not exist!'), $object_name.'.php', false);
          }
        }else {
          //call_user_method_array(array_slice($controller, 0,2), $obj, array_slice($controller, 2,count($controller)));
          call_user_func_array(
            [$obj, $controller[1]], // Controller class & method name
            array_slice($controller, 2,count($controller)) // parameter value
          );
        }
      }else{
        die('dsController not extended in this Controller or contructor not called.');
      }
    }else{
      $this->on_attach_api($requestTarget, $structure_app, $first_load, $path);
    }
  }
  private function on_attach_api($requestTarget, $structure_app, $first_load, $path)
  {
      $iRouteStep = ($structure_app == Key::MULTI ? 1 : 0);
      $apiRequest = dsSystem::fill_text($requestTarget[0 + $iRouteStep]);
      $apiRequest = $apiRequest == STRING_EMPTY ? $first_load : $apiRequest;
      $filename   = Indexes::$DIR_API.ucfirst($apiRequest).'.php';
      $apiTarget  = count($requestTarget) > 1 + $iRouteStep ? $requestTarget[1 + $iRouteStep] : Key::CHAR_SLASH;
      
      $indexOf    = strpos($apiTarget,'?');
      $apiRoute   = $indexOf < 0 ? substr($apiTarget, 0, $indexOf) : $apiTarget;
      // set attribute of api requirement
      API::route($apiRequest, $apiRoute);
      if(file_exists($filename)){
        require_once $filename;
      }
      else{
        $ex = new Exception($filename.' file is not found or not registered!');
        $ex = new dsException($ex,'URI Address -> '. $path,FALSE);
      }
  }
  public static function route($_routeTarget, $_routeName)
  {
    Route::set_route(site($_routeTarget), $_routeName);
  }
  static function page($__fl=STRING_EMPTY,$__dt = array())
  {
    // Create your security filter here
    Page::__page($__fl, $__dt); // Load file view
  }
}
