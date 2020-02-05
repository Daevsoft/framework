<?php
defined('root') or die('<h3>Sorry, nothing to do here !</h3> Config');

// Configuration
$config = [
	/* -----------------------------------------------------------\
	|			Connection and General Configuration
	\ -----------------------------------------------------------*/

	/* APP NAME
	|	Your web name 
	*/
	'app_name'				=> 'My Web',
	
	/* SERVER HOST
	|	Your database server host
	|	ex : 'sqlx.sample-hosting.xx'
	*/
	'host'					=> $_SERVER['SERVER_NAME'],
	
	/* USERNAME
	|	Your host name/username
	*/
	'username'				=> 'root',

	/* PASSWORD
	|	Your database host password 
	*/
	'password'				=> '',

	/* Driver
	|	You can customize driver such as Mysql or Sql Server
	|	 Able driver 	: - mysql
	|					  - sqlsrv
	|	 On Development	: - mssql
	*/
	'driver'				=> 'mysql',

	/* DATABASE
	|	your selected database
	|	Multiple database coming soon
	*/
	'database'				=> '',

	/* PORT
	|	Default Port :
	|	 - Sql Server = 1433
	|	 - Mysql = 3306
	*/
	'port'					=> 3306,

	/* COOKIE EXPIRED DAYS
	|	clear cookie for the days 
	*/
	'cookie_expired'		=>  30,
	
	/* STATUS REPORTING	
	|	Status is Web Status. It will impact to any crash or error reporting
	|	if published any crash/error reporting will be hidden.
	|	option : 
	|	- dev (development)
	|	- pub (published)
	*/
	'status'				=> 'dev',

	/* -----------------------------------------------------\
	|			Structure and Folder Configuration
	\ -----------------------------------------------------*/

	/* STRUCTURE APP
	|	determine the application structure to be used as main structure
	|	options :
	|	 - api
	|	 - mvc
	|	 - both
	*/
	'structure_app'			=> 'both',

	/* MODEL PATH
	|	You can move your folder any where you want
	|	but default is app/models 
	*/
	'model_path'			=> 'app/models',

	// CONTROLLER PATH
	'controller_path'		=> 'app/controllers',
	
	// VIEW PATH
	'view_path'				=> 'app/views',

	/* FIRST LOAD
	|	First load is determine the first uri will be loaded
	|	use ApiName when 'structure_app' is 'api' or
	|	use ControllerName when 'structure_app' is 'mvc' or 'both'
	|	sample for 'controllers/MainController.php' is 'main'
	*/
	'first_load'			=> 'archer', 

	/* 404 Page Not Found
	|	If address url not found the system will open file view in the views/404/index.php.
	|	Fill name if file 404 able in views/404.
	|	Default empty it mean call the index.php in 404 folder.
	*/
	'404_not_found_file'			=> '',

	/* COMPOSER PATH
	|	Composer path will used to read the autoload
	|	file from composer such as library from other source. 
	*/
	'composer_path'			=> 'vendor',



	/* --------------------------------------------------\
	|			Localization Configuration
	\ --------------------------------------------------*/

	/* LANGUAGE
	|	set default language for translating words
	|	file language in app/lang
	|	it's optional, just keep it blank if not used 
	*/
	'language'				=> 'en',

	/* LANGUAGE TYPE
	|	Choose your language source from file type
	|	Option : - php
	| 			 - json
	*/
	'language_type'			=> 'php'
];

// Autoload libraries
/* write like this :
	+--------------------------------------------
	|	['LibrariesName1','LibrariesName1']
	+--------------------------------------------

	or with alias like :
	+--------------------------------------------
	|	'aliasName' => 'LibrariesName'
	+--------------------------------------------

	and to call object instance in anywhere use :
	+--------------------------------------------
	|	_get('aliasName')
	+--------------------------------------------

	or just import only :
	+--------------------------------------------
	|	'fileName' => false
	+--------------------------------------------
	false : is mean that not to create instance automatically
*/
$autoload = [
	Key::LIBRARIES	=> [],
	Key::MODULES	=> ['DbScheme' => false],
	Key::MODELS		=> []
];

// Rename Controller
// used when you want to replace/manipulate your Controller Name
// write like this 'YourController' => 'replaceName'
// For example : 'MyController' => 'admin'
$renameController = [
	'Archer' => 'arc'
];

// RouteList
// write like this 'RouteName' => 'RouteTarget'.
// For example 'myController/myFunction' => 'pageone'
// Then open your browser like 'localhost:8000/pageone' it's mean 'localhost:8000/myController/myFunction'
$routeList = [
];

//IP Address validation
$ipAddress = [
	// Set the limit of IP Client that can access your web
	// For example 192.168.43.*
	'ip_pattern' => '*.*.*.*',
	// For example write listed ip with delimiter by comma ',' 
	// Example : '192.168.137.1, 192.168.137.2, ...'
	// For all ip use 'any'
	'ip_list' 	 => 'any'
];
