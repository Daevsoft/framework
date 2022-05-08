<?php

class dsCore
{
	/**
	 * Author By Muhamad Deva Arofi
	 * Main of class dsCore
	 */
	protected $_access = FALSE;
	protected $Connection;
	protected $_frontend;

	function __construct()
	{
		dsSystem::secure();
		$this->_frontend = new FrontEnd();
		if((uri(0) != Key::API && config('structure_app') == 'both') || config('structure_app') == 'mvc'){
			$this->initSession();
		}
	}

	private function initSession(){
		$root = dirname(dirname(__DIR__));

		ini_set('session.save_handler', 'files');
		$key = 'APP_SESSION';

		$handler = new DsSessionHandler($root.'/storage/session', $key);
		session_set_save_handler($handler, true);
		DsSessionHandler::$handler = $handler;
		// Start the session
		session_start();
		
		// discover session by cookie
		if (isset($_COOKIE[session_name()]) && ($_COOKIE[session_name()] == session_id())) {
		// validate session contents
		if (session('STUFF-SECRET') != true){
			// destroy session and regenerate id
			session_regenerate_id(true); // skip this if you generate your own
			session('STUFF-SECRET', true);
		}
		}else{
			unset($_COOKIE[session_name()]);
			session_destroy();
			session_start();
			session_regenerate_id(true); // skip this if you generate your own
			setcookie(session_name(), session_id());
			session('STUFF-SECRET', true);
		}
		// free memory
		unset($root);
		unset($handler);
		unset($_SESSION);
	}

	public function set_controller()
	{
		// permission to access the controller object
		$this->_access = TRUE;
	}

	public function connect()
	{
		$this->_frontend->setup();
		// free memory
		unset($this->_frontend);
	}

	protected function get_connection()
	{
		try{
			if (config('database') != STRING_EMPTY) {
				$_connection = $this->get_host_connection();
				if(is_null($this->Connection)){
					$this->Connection = new PDO($_connection,config('username'),config('password'));
					$this->Connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}
				return $this->Connection;
			}else {
				$ex = new Exception("Database not found!<br/><b>Please check your configuration file!</b>", 1);
				throw new dsException($ex, 'config/config.inc.php', FALSE);
			}
		}catch(PDOException $ex){
			throw new dsException($ex, 'config/config.inc.php', FALSE);
		}
	}
	private function get_host_connection(){
		$_db_type = $_host_type = STRING_EMPTY;
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