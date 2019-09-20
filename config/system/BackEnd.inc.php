<?php
secure_page();
/*
    BackEnd Class author by Muhamad Deva Arofi

    BackEnd:
    - it can get data from database directly like get one row or table
*/
class BackEnd extends dsCore
{
    protected $pdo_result = NULL;
    protected $sql;
    // call model object
	function call($_model_v){
		return $GLOBALS['__models'][$_model_v];
	}

    public function __construct()
    {

    }
    public function __destruct(){
    }
    

    public static function set_cookie($__nm, $__value)
    { // Set cookie for expired
        global $server;
        $__nm = dsSystem::fill_text($__nm);
        setcookie(md5($__nm), $__value, $server['cookie_expired'] * 60 * 60 * 24);
        return $__value;
    }

    public static function _cookie($__nm)
    { // Get value in cookie
        $__nm = dsSystem::fill_text($__nm);
        return $_COOKIE[md5($__nm)];
    }
    public function row($__q_or_t, $__wh = "", $__bool = "AND") // can be input by query or table, with WHERE condition
    {
        // get connection to mysql
        $this->sql = QueryBuilder::query($__q_or_t, $__wh, $__bool);
        $this->sql['query'] .= " ORDER BY 1 DESC LIMIT 0,1";
        return $this;
    }

    // can be input by query or table, with
    // WHERE condition and Boolean AND/OR.
    // the default is AND
    public function table($__q_or_t, $__wh = STRING_EMPTY, $__bool = 'AND',  $__ord = "ASC")
    {
        $this->sql = QueryBuilder::query($__q_or_t, $__wh, $__bool);
        $this->sql['query'] .= ' ORDER BY 1 '.$__ord;
        // Return as Array
        return $this;
    }

	public function query($sql)
    {
        $this->sql = ['query' => $sql, 'values' => []];
        $this->execute();
        return $this;
    }
    public function fetch_row($pdo_fetch_type = NULL)
    {
        // Execute Query
        $this->execute();
        // get one row
        return $this->fetch($pdo_fetch_type, FALSE);
    }
    public function fetch_all($pdo_fetch_type = NULL)
    {
        // Execute Query
        $this->execute();
        return $this->fetch($pdo_fetch_type);
    }
    // Get pdo result
    private function fetch($pdo_fetch_type, bool $all_data = TRUE){
        if($pdo_fetch_type == NULL)
            $pdo_fetch_type = PDO::FETCH_BOTH;
        if($all_data)
            return $this->pdo_result->fetchAll($pdo_fetch_type);
        else
            return $this->pdo_result->fetch($pdo_fetch_type);
    }
    public function top_all(Int $startRow, Int $limit = 100)
    {
        $this->sql['query'] .= QueryBuilder::limit($startRow, $limit);
        $result_data = [];
        $result_counter = 0;
        do {
            $this->execute(FALSE);
            $result_data = array_merge($this->fetch(NULL, TRUE));
            // add 3000 for loop quick
            $result_counter += 3000;
        } while ($result_counter <= $limit);

        // Execute Query
        return $result_data;
    }
    public function top_row(Int $startRow , Int $limit = 100)
    {
        $this->sql['query'] .= QueryBuilder::limit($startRow, $limit);
        // Execute Query
        return $this->fetch_row();
    }
    public function get_column($__tablename){
        $this->sql['query'] = 'DESC '.string_quote_query($__tablename);
        $this->sql['values'] = [];
        return $this->fetch_all(PDO::FETCH_NAMED);
    }
    public function insert($__table, $__dt)
    {
        $this->sql = QueryBuilder::prepare_insert($__table,$__dt);
        // insert
        $this->execute();
        if ($this->pdo_result) {
            return TRUE;
        }else{
            return FALSE;
        }
    }
    public function insert_get($__table, $__dt) // Insert and get all values
    {
        $this->insert($__table, $__dt);
        return $this->row($__table, $__dt);
    }

    public static function xss_filtering($_value)
    {
        $escape_string = config('connection')."_real_escape_string";
        $escape = $escape_string($_value);
        return $escape;
    }
    public function update($__table, $__dt, $__wh)
    {
        $this->sql = QueryBuilder::update($__table, $__dt, $__wh);
        $this->execute();
        if ($this->pdo_result) {
            return TRUE;
        }else{
            return FALSE;
        }
    }
    private function execute(bool $clean = TRUE)
    {
        try{
            // get connection to PDO
            $pdo = $this->get_connection();
            // Set prepare query
            $data = $pdo->prepare($this->sql['query']);
            // Set values for prepared query
            $data->execute($this->sql['values']);
            // Set pdo_result as PDO Object result
            $this->pdo_result = $data;
            // set null sql properties
            if($clean)
                $this->sql = NULL;
        }catch(PDOException $ex){
            dsSystem::MessageError($ex->getMessage());
        }
    }
    public function delete($__table, $__wh = NULL, $__bool = 'AND')
    {
        $this->sql = QueryBuilder::delete($__table, $__wh, $__bool);
        $this->execute();
        if ($this->pdo_result) {
            return TRUE;
        }else{
            return FALSE;
        }
    }
}
