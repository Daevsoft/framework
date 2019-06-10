<?php 
/**
* Route class Author by Muhamad Deva Arofi
*/
class Route
{
	public static $routeList = array();
	function __construct()
	{
	}
	public static function set_route($__routeTarget, $__routeName)
	{
		self::$routeList[$__routeName] = $__routeTarget;
	}
}