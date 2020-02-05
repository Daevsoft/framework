<?php
/*
    Model:
    - include simple method for build query and execute it directly.
*/
class dsModel extends BackEnd
{
    private $Query;
    private $isWhereDefine = FALSE;
    
    public function __construct() {
    }
    public function data_all($target = NULL)
    {
        if (!string_empty_or_null($this->Query)) {
            $this->sql['query'] = $this->queryClear();
            $this->sql['values'] = [];
            $res = $this->fetch_all($target);
            return $res;
        }else{
            $target = is_null($target) ? PDO::FETCH_BOTH : $target;
            return $this->fetch_all($target);
        }
    }
    public function data_array(){
        return $this->data_all(PDO::FETCH_NUM);
    }
    public function data_class(){
        return $this->data_all(PDO::FETCH_OBJ);
    }
    public function data_assoc(){
        return $this->data_all(PDO::FETCH_ASSOC);
    }
    public function data_bound(){
        return $this->data_all(PDO::FETCH_BOUND);
    }
    public function data_into(){
        return $this->data_all(PDO::FETCH_INTO);
    }
    public function data_lazy(){
        return $this->data_all(PDO::FETCH_LAZY);
    }
    public function data_named(){
        return $this->data_all(PDO::FETCH_NAMED);
    }
    public function data_row($target = NULL)
    {
        if (!string_empty_or_null($this->Query)) {
            $this->sql['query'] = $this->queryClear();
            $this->sql['values'] = [];
            $res = $this->fetch_row($target);
            return $res;
        }else{
            $target = is_null($target) ? PDO::FETCH_BOTH : $target;
            return $this->fetch_row($target);
        }
    }
    public function data_exist()
    {
        $data = $this->data_row();
        die();
        return count($data) > 0;
    }
    public function queryClear()
    {
        // Merge to complete
        $queries = $this->Query;
        // Clear temporary query base
        $this->state_clear();
        return $queries;
    }
    // select($table : String)
    // or
    // select($selectColumn : String, $tableName)
    public function select($arg1, $arg2 = STRING_EMPTY)
    {
        $this->Query = QueryBuilder::select($arg1, $arg2);
        return $this;
    }
    // String Columns = 'columnGroup1, columnGroup2'
    // Array Columns = ['columnGroup1', 'columnGroup2']
    public function groupBy($Columns)
    {
        $group = QueryBuilder::group($Columns);
        $this->Query .= $group;
        return $this;
    }
    public function join($_table, $onCondition)
    {
        $join = QueryBuilder::join($_table, $onCondition);
        $this->Query .= $join;
        return $this;
    }
    private function where_root($arg1, $arg2 = STRING_EMPTY, $operand=STRING_EMPTY, $operator = 'AND'){
        $operand = string_empty($operand) ? '=' : $operand;
        if(is_array($arg1)){
            $this->Query .= !$this->isWhereDefine ? ' WHERE ' : ' '.$arg2.' ' ;
            $i = 1;
            foreach ($arg1 as $col => $value) {
                if($i > 1 )
                    $this->Query .= ' '.$arg2.' ';
                $this->Query .= $col.QueryBuilder::like_separator($arg2, $operand).'\''.$value.'\'';
                $i++;
            }
        }
        if(is_string($arg1)){
            // Check where is empty or not then add AND or WHERE clause
            $this->Query .= !$this->isWhereDefine ? ' WHERE ' : ' '.$operator.' ' ;
            if(string_empty($arg2)){
                $this->Query .= $arg1;
            }else{
                $this->Query .= $arg1.QueryBuilder::like_separator($arg2, $operand).'\''.$arg2.'\'';
            }
        }
        // Set WhereDefine Condition TRUE,
        // that mean WHERE clause have been initialize before
        $this->isWhereDefine = TRUE;
        // Return Object For Next Query
        return $this;
    }
    // function where ($arg1: Array[], $arg2: String)
    public function where($arg1, $arg2 = STRING_EMPTY, $operand='=', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, $operand, $arg3);
    }
    // where x like y
    public function like($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' like ', $arg3);
    }
    // where x = y
    public function equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' = ', $arg3);
    }
    // where x != y
    public function not_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' != ', $arg3);
    }
    // where x > y
    public function greater($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' > ', $arg3);
    }
    // where x >= y
    public function greater_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' > ', $arg3);
    }
    // where x < y
    public function lower($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' < ', $arg3);
    }
    // where x <= y
    public function lower_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' <= ', $arg3);
    }
    public function table($__q_or_t, $__wh = STRING_EMPTY, $__bool = 'AND',  $__ord = "ASC")
    {
        $this->backEnd = $this->table($__q_or_t, $__wh, $__bool,  $__ord);
        return $this;
    }
    public function delete($tableName, $__wh = NULL, $__bool = 'AND')
    {
        $result = false;
        if(!string_empty_or_null($__wh))
            $result = parent::delete($tableName, $__wh);
        else
            $result = parent::delete($tableName, string_part($this->Query, 'WHERE'));
        
        // Clear query state
        $this->state_clear();

        return $result;
    }
    private function state_clear()
    {
        $this->sql = ['query' => STRING_EMPTY, 'values' => STRING_EMPTY];
        $this->Query = 
        $this->Query = STRING_EMPTY;
        $this->isWhereDefine = FALSE;
    }
}
