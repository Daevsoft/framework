<?php
$GLOBALS = array(
	'renameController' => $renameController,
	'routeList' => $routeList,
	'server' => $config,
	'__models' => [] // For models objects
);

// Get global functions
require_once dirname(__DIR__,2).'/app/func/functions.php';
if (! function_exists('config')) {
	function config($_config)
	{
		// Get Config
		return $GLOBALS['server'][$_config];
	}
}
//  cache directory time
$GLOBALS['dir_temporary_record_time'] = require_once(Indexes::$DIR_CACHE_TIME);
if (! function_exists('cache_record')) {
	function cache_record(&$_record_index)
	{
		$dir_temporary_record_time = $GLOBALS['dir_temporary_record_time'];
		// Get Cache Index
		if(isset($dir_temporary_record_time[$_record_index]))
			return $dir_temporary_record_time[$_record_index];
		else
			return STRING_EMPTY;
	}
}

// Seed language for changes from session
$_SESSION['ds_language']['language_seed'] = config('language');

// Set Exception handler
if (! function_exists('dsException')) {
	function dsException($exception){
		new dsException($exception);
	}
}
// Set Notice/Warning handler
if(! function_exists('set_notice_handler')){
	// for any error message
	function __messageHandler($headstr, $color ,$notno, $notstr, $errfile, $errline)
	{
		$errfile = (!isset($GLOBALS['FILENAMES_REAL']) 
		|| string_empty_or_null($GLOBALS['FILENAMES_REAL'])) ? 
		$errfile : $GLOBALS['FILENAMES_REAL']; 
		echo '<div style="font-weight:200;color:'.$color.'"><b>'.$headstr.' ['.$notno.'] :</b> '.$notstr.' @'.$errfile.'('.$errline.')</div>';
	}
	function __noticeHandler($notno, $notstr, $errfile, $errline)
	{
		$_COLOR = 'grey';
		$_HEADSTR = 'BUG';
		if ($notno == E_ERROR || $notno == E_USER_ERROR) {
			$_COLOR = 'red';
			$_HEADSTR = 'ERROR';
		}else if ($notno == E_WARNING || $notno == E_USER_WARNING) {
			$_COLOR = 'orange';
			$_HEADSTR = 'WARNING';
		}
		__messageHandler($_HEADSTR, $_COLOR, $notno, $notstr, $errfile, $errline);
	}
	function set_notice_handler()
	{
		set_error_handler("__noticeHandler", E_ALL);
		// set_error_handler("__noticeHandler", );
	}
}
// If published some error will be hidden.
switch ($config['status']) {
	case Key::DEVELOPMENT:
		// Code for development here
		set_notice_handler();
		set_exception_handler('dsException');
	break;
	case Key::PUBLISHED:
		error_reporting(0);break;
	default:break;
}
// Load Controller class
spl_autoload_register(function($classname)
{
	// generate physical directory file name
	$filename = Indexes::$DIR_ROOT.config('controller_path').Key::CHAR_SLASH.ucfirst($classname).'.php';
	if(file_exists($filename)){
		require_once $filename;
	}else {
		// echo '-'.$classname.'-<br>';
		// page not found function has 3 argument 
		// (condition, alternate_function, argument1, argument2, ...)
		// Page::not_found(config('status') == Key::PUBLISHED, function($args){
			// dsSystem::MessageError(__FILE__,'Error '.$args[0].' Not Found');
		// }, $classname);
	}
});

// Autoload Composer
$composer_path = dirname(dirname(__DIR__)).Key::CHAR_SLASH.config('composer_path').'/autoload.php';
if(file_exists($composer_path))
	require_once $composer_path;

// get Uri Request
if (! function_exists('uri')) {
	function uri($uri_position)
	{
		// Get Uri Address
		$uri = explode(Key::CHAR_SLASH, substr($_SERVER['REQUEST_URI'], 1));
		if(count($uri) < $uri_position) dsSystem::MessageError('URI Not Found at position '. $uri_position);
		return $uri[$uri_position];
	}
}

// get Uri Request
if (! function_exists('view')) {
	function view($view_target, $data = [])
	{
		FrontEnd::page($view_target, $data);
	}
}
// get Uri Request
if (! function_exists('get_view')) {
	function get_view($view_target, $data = [])
	{
		ob_start();
		FrontEnd::page($view_target, $data);
		$view = ob_get_contents();
		ob_end_clean();
		return $view;
	}
}
if (! function_exists('string_empty')) {
	function string_empty(&$_string)
	{
		// Get boolean
		return ($_string == STRING_EMPTY);
	}
}
if (! function_exists('string_empty_or_null')) {
	function string_empty_or_null(&$_string)
	{
		// Get result condition
		return (is_null($_string) || string_empty($_string));
	}
}
if (! function_exists('string_condition')) {
	function string_condition(&$_string1, $_string2 = STRING_EMPTY)
	{
		// Get boolean
		return string_empty_or_null($_string1) ? $_string2 : $_string1;
	}
}
if (! function_exists('string_quote_query')) {
	function string_quote_query(&$_value){
		switch(config('driver')){
			// Mysql Provider SQL Query Support
			case 'mysql':return '`'.$_value.'`';break;
			// Sql Server Provider SQL Query Support
			case 'sqlsrv':return '['.$_value.']';break;
		}
	}
}
if (! function_exists('arr_value')) {
	function arr_value($arr, $key, $other = STRING_EMPTY)
	{
		if(isset($arr) && isset($arr[$key])) return $arr[$key];
		else return $other;
	}
}
if (! function_exists('set_lang')) {
	function set_lang($_lang = STRING_EMPTY){
		$_SESSION['ds_language']['language_seed'] = $_lang;
	}
}
if (! function_exists('get_lang')) {
	function get_lang()
	{
		return $_SESSION['ds_language']['language_seed'];
	}
}
if (! function_exists('lang')) {
	function lang($_msg, $_type = STRING_EMPTY, $_seed = STRING_EMPTY){
		if(!string_empty(config('language'))){
			// Json Or Php
			$_type = string_condition($_type, config('language_type'));
			// ind, en, other
			$_seed = string_condition($_seed, get_lang());
			$_countable = $text = STRING_EMPTY;
			if(string_contains(':', $_msg)){
				$arrMessage = explode(':',$_msg);
				// get key of text language
				$_msg = $arrMessage[0];
				// get count value plural
				$_countable = $arrMessage[1];
			}
			FrontEnd::set_lang($_seed, $_type);
			$lang = FrontEnd::get_lang($_seed, $_type);
			if ($_type == 'php') {
				// translate from index array
				$_langValue = $lang[$_msg];
				if (is_array($_langValue)) {
					foreach ($_langValue as $key => $msg) {
						if($key === $_countable) {
							return $_langValue;
						}else{
							if(string_contains('-',$key)){
								if($key === '-'){
									return $_langValue[$key];
								}else{
									$arrRange = explode('-',$key);
									if($_countable >= $arrRange[0])
										if(isset($arrRange[1]))
											if($_countable <= $arrRange[1])
												return $msg;
								}
							}elseif(string_contains('+',$key)){
								// TODO
							} // end if
						} // end if
					} // end foreach
				}else
					return $_langValue;
				return $_msg;
			}else
				// return translate from index json
				return $lang[0]->$_seed[0]->$_msg;
		}else{
			return $_msg;
		}
	}
}

if (! function_exists('string_contains')) {
	function string_contains($_seed, $_value){
		if(strpos($_value, $_seed) === false)
			return false;
		else
			return true;
	}
}
if (! function_exists('string_crop')) {
	function string_crop(string $_string, int $startIndex , string $_cropTo = STRING_EMPTY, int $offset = 0){
		if(!string_empty($_string)){
			$endIndex = string_empty($_cropTo) ? 0 : strpos($_string, $_cropTo, $offset);
			$endIndex = $endIndex == 0 ? strlen($_string) : $endIndex;
			return substr($_string, $startIndex, $endIndex);
		} return $_string;
	}
}
// string_part = substring start from string_start to string_end
if (! function_exists('string_part')){
	function string_part($string, $string_start, $string_end = NULL)
	{
		$startPos = strpos($string, $string_start);
		$startPos = $startPos >= 0 ? $startPos + strlen($string_start) : 0;
		if($string_end == NULL)
			return substr($string, $startPos);
		else
			return substr($string, $startPos, 
				strlen($string_end) + strpos($string, $string_end));
	}
}
if (! function_exists('str_allow')) {
	function str_allow(bool $_condition, string $_allow = STRING_EMPTY, string $_replace = STRING_EMPTY)
	{
		return $_condition ? $_allow : $_replace;
	}
}
if (! function_exists('site')) {
	function site($_routeLink)
	{
		// Get Uri Address
		return Indexes::$BASE_URL.Key::CHAR_SLASH.$_routeLink;
	}
}
// File downloader from header
if (! function_exists('force_download')) {
	function force_download($_link, $_file)
	{
		// check to verify that the file exists
		header('Location:'.$_link);
		exit();
	}
}
// Direct linking
if (! function_exists('redirect')) {
	function redirect($target=STRING_EMPTY)
	{
		header('Location:'.Indexes::$BASE_URL.Key::CHAR_SLASH.$target);
	}
}
// Input Request will get global variable from _post and _get both.
if (! function_exists('_request')) {
	function _request($__nm)
	{
		$__nm = $_REQUEST[$__nm] or die("Request <b>$__nm</b> not found !");
		dsSystem::fill_text($__nm);
		return $__nm;
	}
}

// Call path file in files folder
if (! function_exists('get_file')) {
	function get_file($_path_file = STRING_EMPTY)
	{
		$_path = Indexes::$BASE_URL.Key::CHAR_SLASH.'assets/files'.Key::CHAR_SLASH.$_path_file;
		return $_path;
	}
}
if (! function_exists('secure_page')) {
	function secure_page()
	{
		$config = $GLOBALS['server'];
		(defined('root') && !empty($config)) or die('<h3>Sorry, nothing to do here !</h3> Secure');
	}
}

if (! function_exists('css_source')) {
	function css_source($_fileName)
	{
		echo '<link rel=\'stylesheet\' href=\'/assets/'.('css'.Key::CHAR_SLASH.$_fileName).'.css\'>';
	}
}
if (! function_exists('css_url')) {
	function css_url($_fileName)
	{
		return '<link rel=\'stylesheet\' type=\'text/css\' href=\'/assets/css/'.$_fileName.'.css\'>';
	}
}

if (! function_exists('js_source')) {
	function js_source($_fileName)
	{
		echo '<script type=\'text/javascript\' src=\'/assets/'.('js'.Key::CHAR_SLASH.$_fileName).'.js\'></script>';
	}
}

if (! function_exists('js_url')) {
	function js_url($_fileName)
	{
		return '<script type=\'text/javascript\' src=\'/assets/js/'.$_fileName.'.js\'></script>';
	}
}

if (! function_exists('assets_source')) {
	function assets_source($_fileName)
	{
		return Indexes::$BASE_URL.(Key::CHAR_SLASH.'assets'.Key::CHAR_SLASH.$_fileName);
	}
}