<?php

class dsCore
{
	/**
	 * Author By Muhamad Deva Arofi
	 * Main of class dsCore
	 */
	protected $_access = FALSE;
	protected $_frontend;
	protected $Connection;

	function __construct()
	{
		dsSystem::secure();
	}

	public function set_controller()
	{
		$this->_access = TRUE;
	}

	public function connect()
	{
		if(is_null($this->_frontend))
			$this->_frontend = new FrontEnd();
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
