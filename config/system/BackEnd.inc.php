<?php
secure_page();
define('NUM', PDO::FETCH_NUM);
define('ASSOC', PDO::FETCH_ASSOC);
define('BOTH', PDO::FETCH_BOTH);
define('BOUND', PDO::FETCH_BOUND);
define('CLASS', PDO::FETCH_CLASS);
define('INTO', PDO::FETCH_INTO);
define('LAZY', PDO::FETCH_LAZY);
define('NAMED', PDO::FETCH_NAMED);
define('OBJ', PDO::FETCH_OBJ);

/*
    BackEnd Class author by Muhamad Deva Arofi

    BackEnd:
    - it can get data from database directly like get one row or table
*/
class BackEnd extends dsSystem
{
    public static $pdo_result = NULL;
    public static $sql = NULL;
	function call($_model_v){ // call model object
		return $GLOBALS['__models'][$_model_v];
	}

    public function __construct()
    {

    }
    public function __destruct(){
    }
    

    public function set_cookie($__nm, $__value)
    { // Set cookie for expired
        global $server;
        $__nm = dsSystem::fill_text($__nm);
        setcookie(sha1($__nm), $__value, $server['cookie_expired'] * 60 * 60 * 24);
        return $__value;
    }

    public function _cookie($__nm)
    { // Get value in cookie
        $__nm = dsSystem::fill_text($__nm);
        return $_COOKIE[sha1($__nm)];
    }

    public static function model($mod__,$alias__)
    { // Add object model into variable __models
	    require_once config('model_path').'/'.$mod__.'.php';
    	$GLOBALS['__models'][$alias__] = new $mod__();
    }
    public static function row($__q_or_t, $__wh = "", $__bool = "AND") // can be input by query or table, with WHERE condition
    {
        // get connection to mysql
        $__q = QueryBuilder::query($__q_or_t, $__wh, $__bool);
        $__q['query'] .= " ORDER BY 1 DESC LIMIT 0,1";
        self::execute($__q);
        return __CLASS__;
    }

    // can be input by query or table, with
    // WHERE condition and Boolean AND/OR.
    // the default is AND
    public static function table($__q_or_t, $__wh = STRING_EMPTY, $__bool = 'AND',  $__ord = "ASC")
    {
        $__q = QueryBuilder::query($__q_or_t, $__wh, $__bool);
        $__q['query'] .= ' ORDER BY 1 '.$__ord;
        // Execute Query
        self::execute($__q);
        // Return as Array
        return __CLASS__;
    }

	public static function query($sql)
    {
        self::execute(['query' => $sql, 'values' => []]);
        return __CLASS__;
    }
    public static function fetch_row($target = NULL)
    {
        if($target == NULL)
            $target = BOTH;
        return self::$pdo_result->fetch($target);
    }
    public static function fetch_all($target = NULL)
    {
        if($target == NULL)
            $target = BOTH;
        return self::$pdo_result->fetchAll($target);
    }
    static function insert($__table, $__dt)
    {
        $__q = QueryBuilder::prepare_insert($__table,$__dt);
        // insert
        self::execute($__q);
        if (self::$pdo_result) {
            return TRUE;
        }else{
            return FALSE;
        }
    }

    static function insert_get($__table, $__dt) // Insert and get all values
    {
        self::insert($__table, $__dt);
        return self::row($__table, $__dt);
    }

    static function xss_filtering($_value)
    {
        $escape_string = config('connection')."_real_escape_string";
        $escape = $escape_string($_value);
        return $escape;
    }

    static function update($__table, $__dt, $__wh)
    {
        $__q = QueryBuilder::update($__table, $__dt, $__wh);
        self::execute($__q);
        if (self::$pdo_result) {
            return TRUE;
        }else{
            return FALSE;
        }
    }
    private static function execute($__q)
    {
        try{
            // get connection to PDO
            $pdo = dsCore::get_connection();
            // Set prepare query
            $data = $pdo->prepare($__q['query']);
            // Set values for prepared query
            $data->execute($__q['values']);
          // Set pdo_result as PDO Object result
            self::$pdo_result = $data;
        }catch(PDOException $ex){
            dsSystem::MessageError($ex->getMessage());
        }
    }
    static function delete($__table, $__wh, $__bool = 'AND')
    {
        $__q = QueryBuilder::delete($__table, $__wh, $__bool);
        self::execute($__q);
        if (self::$pdo_result) {
            return TRUE;
        }else{
            return FALSE;
        }
    }
}
