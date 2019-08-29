<?php
defined('root') or die('<h3>Sorry, nothing to do here !</h3> Config');

// your web server host (ex:localhost/index.php)
// (default: $_SERVER['HTTP_HOST'].'/index.php')
define('HTTP_HOST', $_SERVER['HTTP_HOST']); // .'/index.php'

// Configuration application
$server = [

	// APP NAME
	// Your web name
	'app_name'				=> 'My App',
	
	// SERVER HOST
	// your database server host
	'host'					=> $_SERVER['SERVER_NAME'],//'sql164.main-hosting.eu',
	
	// USERNAME
	'username'				=> 'root', // your database username

	// PASSWORD
	'password'				=> '', // your database password

	// Driver
	// You can customize driver such as Mysql or Sql Server
	// 		Able driver : 	- mysql
	//					  	- sqlsrv
	// 		On Development: - mssql
	'driver'				=> 'mysql',

	// DATABASE
	// your selected database
	// Multiple database coming soon
	'database'				=> '',

	// PORT
	// Default Port :
	// 		- Sql Server = 1433
	// 		- Mysql = 3306
	'port'					=> '3306',

	// COOKIE EXPIRED DAYS
	// clear cookie for the days
	'cookie_expired'		=>  30,

	// MODEL PATH
	// You can move your folder any where you want
	// if you want to save it with your Library is up to You
	// but default is app/models
	'model_path'			=> 'app/models',

	// CONTROLLER PATH
	'controller_path'		=> 'app/controllers',
	
	// VIEW PATH
	'view_path'				=> 'app/views',

	// LANGUAGE
	// set default language for translating words
	// file language in app/lang
	// it's optional, just keep it blank if not used
	'language'				=> 'en',

	// LANGUAGE TYPE
	// Choose your language source from file type
	// Option : - php
	// 			- json
	'language_type'			=> 'php',

	// STRUCTURE APP
	// determine the application structure to be used as main structure
	// options :
	// - api
	// - mvc
	// - both
	'structure_app'			=> 'both',

	// FIRST LOAD
	// First load is determine the first uri will be loaded
	'first_load'			=> 'Welcome',

	// COMPOSER PATH
	// Composer path will used to read the autoload
	// file from composer such as library from other source.
	'composer_path'			=> 'vendor',

	/* STATUS REPORTING
	Status is Web Status. It will impact to any crash or error reporting
	if published any crash/error reporting will be hidden.
	option : 
		- dev (development)
		- pub (published)
	*/
	'status'				=> 'dev',

	// 404 Page Not Found
	// If address url not found the system will open file view in the views/404/index.php.
	// Fill name if file 404 able in views/404.
	// Default empty it mean call the index.php in 404 folder.
	'404_not_found_file'			=> ''
];

// Autoload libraries
// write like this :
//		('LibrariesName1','LibrariesName1')
//
// or with alias like :
// 		'aliasName' => 'LibrariesName'
//
// For example : 'Cart' => 'mycart'
$autoload = [
	Key::LIBRARIES	=> [],
	Key::MODULES		=> [],
	Key::MODELS		=> []
];

// Rename Controller
// used when you want to replace/manipulate your Controller Name
// write like this 'YourController' => 'replaceName'
// For example : 'MyController' => 'admin'
$renameController = [
	'Welcome' => 'oke'
];

// RouteList
// write like this 'RouteName' => 'RouteTarget'.
// For example 'myController/myFunction' => 'pageone'
// Then open your browser like 'localhost:8000/pageone' same as 'localhost:8000/myController/myFunction'
$routeList = [
	'oke/index' => 'sample',
	'oke/other' => 'pass'
];

//IP Address validation
$ipAddress = [
	// Set limit IP Client who can access your web
	// For example 192.168.43.*
	'ip_pattern' => '*.*.*.*',
	// For example write listed ip with delimiter by comma ',' 
	// Example : '192.168.137.1, 192.168.137.2'
	// For all ip use 'any'
	'ip_list' 	 => 'any'
];
