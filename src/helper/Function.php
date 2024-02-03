<?php


use Ds\AppIndex;
use Ds\Foundation\View\Slot;
use Ds\Foundations\View\PageProvider;

if (! function_exists('asset')) {
	function asset($_fileName)
	{
		return AppIndex::$BASE_ASSETS.$_fileName;
	}
}
if (! function_exists('get_slot')) {
	function get_slot($_fileName)
	{
		echo Slot::getSlot($_fileName);
	}
}

function view($viewname = 'index', $data = []){
	if($viewname != null){
			$page = new PageProvider();
			$page->__page($viewname, $data);
	}
}
function session($key, $value = NULL)
{
	if($value === NULL) 
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	$_SESSION[$key] = $value;
}
function unsession($key)
{
	unset($_SESSION[$key]);
}
function session_end()
{
	session_destroy();
}
function flash($key, $defaultValue = NULL)
{
	$key = 'flash__'.$key;
	$flash = session($key) ?? $defaultValue;
	unsession($key);
	return $flash;
}
function set_flash($key, $content)
{
	session('flash__'.$key, $content);
}