<?php
/*
    Model:
    - include simple method for build query and execute it directly.
*/
class dsModel
{
    private $Query;
    private $where;
    private $backEnd;
    public function __construct() {
    }
    public function data_all($target = NULL)
    {
        if (!is_null($this->backEnd)) {
            $res = $this->backEnd::fetch_all($target);
            $this->backEnd = NULL;
            return $res;
        }else{
            $target = is_null($target) ? PDO::FETCH_BOTH : $target;
            return BackEnd::query($this->queryClear())::fetch_all($target);
        }
    }
    public function data_array(){
        return $this->data_all(PDO::FETCH_BOTH);
    }
    public function data_class(){
        return $this->data_all(PDO::FETCH_OBJ);
    }
    public function data_assoc(){
        return $this->data_all(PDO::FETCH_ASSOC);
    }
    public function data_numeric(){
        return $this->data_all(PDO::FETCH_NUM);
    }
    public function data_both(){
        return $this->data_all(PDO::FETCH_BOTH);
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
        if (!is_null($this->backEnd)) {
            $res = $this->backEnd::fetch_row($target);
            $this->backEnd = NULL;
        }else{
            return BackEnd::query($this->queryClear())::fetch_row($target);
        }
    }
    public function queryClear()
    {
        return $this->Query.' '.$this->where;
    }
    // select($table : String)
    // or
    // select($selectColumn : String, $tableName)
    public function select($arg1, $arg2 = '')
    {
        $this->Query = QueryBuilder::select($arg1, $arg2);
        return $this;
    }
    public function join($_table, $onCondition)
    {
        $join = QueryBuilder::join($_table, $onCondition);
        $this->Query .= $join;
        return $this;
    }
    private function where_root($arg1, $arg2 = '', $operand='', $arg3 = 'AND'){
        $operand = string_empty($operand) ? '=' : $operand;
        if(is_array($arg1)){
            $this->where .= string_empty_or_null($this->where) ? ' WHERE ' : ' '.$arg2.' ' ;
            $i = 1;
            $len = count($arg1);
            foreach ($arg1 as $col => $value) {
                if($i > 1 )
                    $this->where .= ' '.$arg2.' ';
                $this->where .= $col.QueryBuilder::like_separator($arg2, $operand).'\''.$value.'\'';
                $i++;
            }
        }
        if(is_string($arg1)){
            // Check where is empty or not then add AND or WHERE clause
            $this->where .= string_empty_or_null($this->where) ? ' WHERE ' : ' '.$arg3.' ' ;
            if(string_empty($arg2)){
                $this->where .= $arg1;
            }else{
                $this->where .= $arg1.QueryBuilder::like_separator($arg2, $operand).'\''.$arg2.'\'';
            }
        }
        return $this;
    }
    // function where ($arg1: Array[], $arg2: String)
    public function where($arg1, $arg2 = '', $operand='=', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, $operand, $arg3);
    }
    // where x like y
    public function like($arg1, $arg2 = '', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' like ', $arg3);
    }
    // where x = y
    public function equal($arg1, $arg2 = '', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' = ', $arg3);
    }
    // where x != y
    public function not_equal($arg1, $arg2 = '', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' != ', $arg3);
    }
    // where x > y
    public function greater($arg1, $arg2 = '', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' > ', $arg3);
    }
    // where x >= y
    public function greater_equal($arg1, $arg2 = '', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' > ', $arg3);
    }
    // where x < y
    public function lower($arg1, $arg2 = '', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' < ', $arg3);
    }
    // where x <= y
    public function lower_equal($arg1, $arg2 = '', $arg3 = 'AND')
    {
        return $this->where_root($arg1, $arg2, ' <= ', $arg3);
    }
    public function table($__q_or_t, $__wh = STRING_EMPTY, $__bool = 'AND',  $__ord = "ASC")
    {
        $this->backEnd = BackEnd::table($__q_or_t, $__wh, $__bool,  $__ord);
        return $this;
    }
}
