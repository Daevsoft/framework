<?php
/**
* Session class
* Author by Muhamad Deva Arofi
*/

function set_session($__key, $__val)
{
	$_SESSION[md5($__key)] = $__val;
}
function get_session($__key)
{
	return $_SESSION[md5($__key)];
}
function unset_session($__key)
{
	unset($_SESSION[md5($__key)]);
}
class Session
{

	function __construct()
	{
	}
	public function set($__key, $__val)
	{
		$_SESSION[md5($__key)] = $__val;
	}
	public function get($__key)
	{
		return $_SESSION[md5($__key)];
	}
	public function set_flash($__key, $__val = '')
	{
		$_SESSION[$__key] = $__val;
	}
	public function flash($__key)
	{
		$_VALUE = isset($_SESSION[$__key]) ? $_SESSION[$__key] : "";
		unset($_SESSION[$__key]);
		return $_VALUE;
	}
	public function remove($__key)
	{
		unset($_SESSION[md5($__key)]);
	}
	public function exist($__key)
	{
		return isset($_SESSION[md5($__key)]);
	}
}
