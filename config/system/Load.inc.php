<?php
/**
 * Load class for load file
 */
$_ms; // Array for load class modules,libraries,controller
if (count($autoload[Key::LIBRARIES])) {
	Load::load_libraries_and_modules($autoload[Key::LIBRARIES], Key::LIBRARIES);
}
if (count($autoload[Key::MODULES])) {
	Load::load_libraries_and_modules($autoload[Key::MODULES],Key::MODULES);
}
if (count($autoload[Key::MODELS])) {
	Load::load_libraries_and_modules($autoload[Key::MODELS],Key::MODELS);
}
function _get($_ind)
{
    global $_ms;
    $_mz = $_ms[$_ind] or printf("<h3>Object <i>$_ind</i> not registered !</h3>");
	return $_mz;
}
function _set($_initVar, $objInstance = NULL){

}
class Load extends dsSystem
{
    public function __construct()
    {
    	global $_ms;
		$_ms = is_null($_ms) ? array() : $_ms;
    }

    private static function load_dir($__target, $__alias, $__dir, $InstanceClass = true, $_params = [])
    {
			global $_ms;
			$_target_location_dirname = Key::D_BACK. Key::D_APP. $__dir. Key::CHAR_SLASH. $__target.'.php';
			if(file_exists($_target_location_dirname)){
				require_once $_target_location_dirname;
				if ($InstanceClass && $_ms) {
					$__alias = ($__alias == '') ? $__target : $__alias;
					if(count($_params) > 0){
						$r_object = new ReflectionClass($__target);
						$_ms[$__alias] = $r_object->newInstanceArgs($_params);
					}else{
						$_ms[$__alias] = new $__target();
					}
				}
			}else{
				parent::MessageError($__target,'Cannot load object <b><i>'.$__target.'</i></b> from <b>'.ucfirst($__dir).'</b>, cause <i>'.$__target . '</i> not found!');
			}
    }


    static function load_libraries_and_modules($_dataList, $target)
    {
		foreach ($_dataList as $key => $_libName) {
			if(is_array($_libName)){
				self::load_dir($_libName[0], $key , $target, true, array_slice($_libName, 1));
			}
			if(is_string($_libName)){
				if ($target == Key::LIBRARIES) {
					self::libraries($_libName, (is_numeric($key) ? $_libName : $key ));
				}else{
					self::module($_libName, (is_numeric($key) ? $_libName : $key ));
				}
			}
		}
    }

	static function inc_module($module_target)
	{
		self::load_dir($module_target, '', Key::MODULES, false);
	}

	static function inc_libraries($libraries_target)
	{
		self::load_target($libraries_target, Key::LIBRARIES);
	}

	static function load_target($target, $folderName){
		if(is_array($target)){
			foreach ($target as $sub) {
				self::load_dir($sub, '', $folderName, false);
			}
		}else if (is_string($target)) {
			self::load_dir($target, '', $folderName, false);
		}
	}

    public static function module($module_target , $alias = '', $_params = [])
	{
		$module_target = ucfirst($module_target);
		self::load_dir($module_target, $alias, Key::MODULES, true, $_params);
	}
	public static function libraries($lib_target, $alias = '', $_params = [])
	{
		self::load_dir($lib_target, $alias, Key::LIBRARIES, true, $_params);
	}

	public static function controller($con_target, $alias = '', $_params = [])
	{
		$con_target = ucfirst($con_target);
		self::load_dir($con_target.Key::CONTROLLER, $alias, Key::CONTROLLERS, true, $_params);
	}

	public static function model($mod_target, $alias = '', $_params = [])
	{
		$mod_target = ucfirst($mod_target);
		self::load_dir($mod_target.Key::MODEL, $alias, Key::MODELS, true, $_params);
	}

	static function object($alias_name) // get object with alias key
	{
		global $_ms;
		return $_ms[$alias_name];
	}
}
