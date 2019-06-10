<?php

class dsCore
{
	/**
	 * Author By Muhamad Deva Arofi
	 * Main of class dsCore
	 */
	public static $_access;
	private $_fend;
	private $_bend;
	private $folder_target;
	private static $Connection;

	function __construct()
	{
		dsSystem::secure();
		if(is_null($this->_fend))
			$this->_fend = new FrontEnd();
		// For queryBuilder !!!
		if(is_null(BackEnd::$sql))
			BackEnd::$sql = new QueryBuilder(); 
	}

	public static function set_controller()
	{
		self::$_access = TRUE;
	}

	public function connect()
	{
		// Set Default Controller First Load
		$req_uri = (!isset($_SERVER['REQUEST_URI'])) ?
		config('first_load') : trim($_SERVER['REQUEST_URI'],'/');
		$this->folder_target = ucfirst($req_uri);
		$this->_fend->view($GLOBALS['routeList'],$GLOBALS['renameController'],$this->folder_target);
	}

	static function get_connection()
	{
		try{
			if (config('database') != '') {
				$_connection = self::get_host_connection();
				if(is_null(self::$Connection)){
					self::$Connection = new PDO($_connection,config('username'),config('password'));
					self::$Connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}
				return self::$Connection;
			}else {
				return 'No connection !';
			}
		}catch(PDOException $ex){
			$e = new dsException($ex, 'config/config.php', FALSE);
		}
	}
	static function get_host_connection(){
		$_db_type = $_host_type = '';
		switch(config('driver')){
			case 'mysql':
				$_db_type = 'dbname';
				$_host_type = 'host';break;
			case 'sqlsrv':
				$_db_type = 'Database';
				$_host_type = 'Server';break;
		}
		
		return config('driver').':'.$_host_type.'='.
		config('host').';'.$_db_type.'='.
		config('database').';';
	}
}
