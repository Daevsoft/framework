<?php
/*
    Model:
    - include simple method for build query and execute it directly.
*/
class dsModel extends BackEnd
{
    private $Query;
    private $Values;
    private $isWhereDefine = FALSE;
    private $isHavingDefine = FALSE;
    
    public function __construct() {
    }
    public function getPattern($table)
    {
        $pattern = [];
        $this->Query = QueryBuilder::descTable($table);
        
        $data = $this->get_array();
        $len = count($data);
        
        for ($i=0; $i < $len; $i++) { 
            $pattern[$data[$i][0]] = STRING_EMPTY;
        }
        return $pattern;
    }
    public function get_all($target = NULL)
    {
        if (!string_empty_or_null($this->Query)) {
            $this->sql = $this->get_sql();
            $res = $this->fetch_all($target);
            return $res;
        }else{
            $target = is_null($target) ? PDO::FETCH_BOTH : $target;
            return $this->fetch_all($target);
        }
    }
    public function get_array(){
        return $this->get_all(PDO::FETCH_NUM);
    }
    public function get_object(){
        return $this->get_all(PDO::FETCH_OBJ);
    }
    public function get_assoc(){
        return $this->get_all(PDO::FETCH_ASSOC);
    }
    public function get_bound(){
        return $this->get_all(PDO::FETCH_BOUND);
    }
    public function get_into(){
        return $this->get_all(PDO::FETCH_INTO);
    }
    public function get_lazy(){
        return $this->get_all(PDO::FETCH_LAZY);
    }
    public function get_named(){
        return $this->get_all(PDO::FETCH_NAMED);
    }
    public function get_row_array(){
        return $this->get_row(PDO::FETCH_NUM);
    }
    public function get_row_object(){
        return $this->get_row(PDO::FETCH_OBJ);
    }
    public function get_row_assoc(){
        return $this->get_row(PDO::FETCH_ASSOC);
    }
    public function get_row_bound(){
        return $this->get_row(PDO::FETCH_BOUND);
    }
    public function get_row_into(){
        return $this->get_row(PDO::FETCH_INTO);
    }
    public function get_row_lazy(){
        return $this->get_row(PDO::FETCH_LAZY);
    }
    public function get_row_named(){
        return $this->get_row(PDO::FETCH_NAMED);
    }
    public function get_row($target = NULL)
    {
        if (!string_empty_or_null($this->Query)) {
            $this->sql = $this->get_sql();
            $res = $this->fetch_row($target);
            return $res;
        }else{
            $target = is_null($target) ? PDO::FETCH_BOTH : $target;
            return $this->fetch_row($target);
        }
    }
    public function get_exist()
    {
        $data = $this->get_row();
        return is_array($data) && count($data) > 0;
    }
    public function get_query(){
        return $this->Query;
    }
    private function get_sql()
    {
        // Merge to complete
        $queries = $this->Query;
        // Merge to complete
        $value = $this->Values;
        // Clear temporary query base
        $this->state_clear();
        return [
            'query' => $queries, 
            'values' => $value
        ];
    }
    // select($table : String)
    // or
    // select($selectColumn : String, $tableName)
    public function select($columns, $from = STRING_EMPTY)
    {
        $this->Query = QueryBuilder::select($columns, $from);
        return $this;
    }
    public function distinct($columns, $from = STRING_EMPTY)
    {
        $this->Query = QueryBuilder::select($columns, $from, TRUE);
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
    public function asc($column_name){
        $this->Query .= QueryBuilder::order_by($column_name, 'ASC');
        return $this;
    }
    public function limit($start = 0, $to = 100)
    {
        $this->Query .= QueryBuilder::limit($start, $to);
        return $this;
    }
    public function desc($column_name){
        $this->Query .= QueryBuilder::order_by($column_name, 'DESC');
        return $this;
    }
    public function join($_table, $onCondition)
    {
        return $this->join_root($_table, $onCondition);
    }
    private function join_root(&$_table, &$onCondition, $type = STRING_EMPTY)
    {
        $this->Query .= $type.QueryBuilder::join($_table, $onCondition);
        return $this;
    }
    public function left_join($_table, $onCondition)
    {
        return $this->join_root($_table, $onCondition, ' LEFT');
    }
    public function right_join($_table, $onCondition)
    {
        return $this->join_root($_table, $onCondition, ' RIGHT');
    }
    public function inner_join($_table, $onCondition)
    {
        return $this->join_root($_table, $onCondition, ' INNER');
    }
    public function outer_join($_table, $onCondition)
    {
        return $this->join_root($_table, $onCondition, ' OUTER');
    }
    public function full_join($_table, $onCondition)
    {
        return $this->join_root($_table, $onCondition, ' FULL');
    }
    private function where_root($arg1, $arg2 = STRING_EMPTY, $operand=STRING_EMPTY, $operator = 'AND', $mergeOpt = 'AND', $isSeparate){
        $operand = string_empty($operand) ? '=' : $operand;
        
        if(!$this->isWhereDefine){
            $this->Query .= ' WHERE';
        }else{
            // if(is_array($arg1) && count($arg1) > 1)
                $this->Query .= ' '.$mergeOpt;
        }

        if($isSeparate)
            $this->Query .= ' (';

        if(is_array($arg1)){
            if (string_empty($arg2))
                $arg2 = $operator;

            $opt = true;
            foreach ($arg1 as $col => $value) {
                $this->Query .= ($opt) ? '' : ' '.$arg2;
                if(is_string($col)){
                    $this->Query .= ' '.$col.QueryBuilder::like_separator('?', $operand).'?';
                    $this->Values[] = $value;
                }else{
                    $this->Query .= ' ?';//.$value;
                    $this->Values[] = $value;
                }
                $opt = false;
            }
            $this->setWhereClause();
        }
        if(is_string($arg1)){
            // Check where is empty or not then add AND or WHERE clause
            $this->Query .= (!$this->isWhereDefine) ? '' : ' '.$operator;
            $this->setWhereClause();
            if(string_empty($arg2)){
                $this->Query .= ' ?';
                $this->Values[] = $arg1;
            }else{
                $this->Query .=  ' '.$arg1.QueryBuilder::like_separator('?', $operand).'?';
                $this->Values[] = $arg2;
            }
        }
        
        if($isSeparate)
            $this->Query .= ') ';
        // Return Object For Next Query
        return $this;
    }
    private function setWhereClause($boolean = TRUE)
    {
        // Set WhereDefine Condition TRUE,
        // that mean WHERE clause have been initialize before
        if($this->isWhereDefine !== $boolean)
            $this->isWhereDefine = $boolean;
    }
    private function setHavingClause($boolean = TRUE){
        if($this->isHavingDefine !== $boolean)
            $this->isHavingDefine = $boolean;
    }
    /*
    $operator {>, <, =, <=, >=}
    */
    // public function having($condition, $operator = STRING_EMPTY, $valueOperatorOrAnd = STRING_EMPTY, $AndOr = 'AND')
    // {
    //     $operator = string_empty($operator) ? 'AND' : $operator;
    //     $this->Query .= !$this->isHavingDefine ? ' HAVING ' : $operator;
        
    //     if(is_array($condition)){
    //         foreach ($condition as $value) {
    //             $this->Query .=  ? $valueOperatorOrAnd : ' HAVING ';
    //             $this->Query .= $value;
    //             $this->setHavingClause();
    //         }
    //     }
    //     if(is_string($condition)){
    //         $this->Query .= ;
    //     }
    //     $this->setHavingClause();
    // }
    // function where ($arg1: Array[], $arg2: String)
    public function and($arg1, $arg2 = STRING_EMPTY, $operand='=', $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, $operand, $arg3, 'AND', $isSeparate);
    }
    public function or($arg1, $arg2 = STRING_EMPTY, $operand='=', $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, $operand, $arg3, 'OR', $isSeparate);
    }
    // function where ($arg1: Array[], $arg2: String)
    public function where($arg1, $arg2 = STRING_EMPTY, $operand='=', $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, $operand, $arg3, 'AND', $isSeparate);
    }
    // where x like y
    public function like($arg1, $arg2 = STRING_EMPTY, $arg3 = 'OR', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, ' like ', $arg3, 'AND', $isSeparate);
    }
    // where x = y
    public function equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, ' = ', $arg3, 'AND', $isSeparate);
    }
    // where x != y
    public function not_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, ' != ', $arg3, 'AND', $isSeparate);
    }
    // where x > y
    public function greater($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, ' > ', $arg3, 'AND', $isSeparate);
    }
    // where x >= y
    public function greater_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, ' >= ', $arg3, 'AND', $isSeparate);
    }
    // where x < y
    public function lower($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, ' < ', $arg3, 'AND', $isSeparate);
    }
    // where x <= y
    public function lower_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    {
        return $this->where_root($arg1, $arg2, ' <= ', $arg3, 'AND', $isSeparate);
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
        $this->Query = STRING_EMPTY;
        $this->Values = [];
        $this->isWhereDefine = 
        $this->isHavingDefine = FALSE;
    }
}
