<?php 
/**
* QueryBuilder
* Author by Muhamad Deva Arofi
*    QueryBuilder:
*    - generate query for BackEnd class
*/
class QueryBuilder
{
	function __construct()
	{
    }
    /* 
    arg1 = collection of column
    arg2 = table name
    if arg1 = array then
        arg1 as column
    if arg2 is empty then
        arg1 as table name
    */
    public static function select($arg1, $arg2, $distinct = FALSE){
        $q = 'SELECT ';
        if($distinct) $q .= 'DISTINCT ';
        if ($arg2 != STRING_EMPTY) {
            $col = STRING_EMPTY;
            if(is_array($arg1)){
                /*
                if array
                [
                    'ColumnName' => 'AliasName'
                ];
                */
                $len = count($arg1);
                $i = 1;
                foreach ($arg1 as $column => $as) {
                    if($i <= $len && $i > 1)
                        $col .= ',';
                    
                    $col .= str_allow(
                        is_string($column),
                        $column . ' AS \'' . $as . '\'', 
                        $as);
                    $i++;
                }
            }
            // if string ('columnName, columnName,..')
            if(is_string($arg1))
                $col = $arg1; 
            
            $q .= $col.' FROM '.$arg2;
        }else{
            $q .= '* FROM '.$arg1;
        }
        return $q;
    }
    public static function limit($start, $end){
        return ' LIMIT '.$start. ', '.$end;
    }
    public static function join($_table, $onCondition)
    {
        $on = STRING_EMPTY;
        // Create " 'tableName' asName "
        $_tableRef = $_table;
        // $_tableExp = 'TableName' asName
        if(is_array($onCondition)){
            $index = 1;
            foreach ($onCondition as $key => $value) {
                if($index > 1){
                    $on .= ' AND ';
                }
                $on .= $key.'='.$value;
                $index++;
            }
        }else if (is_string($onCondition)) {
            $on = $onCondition;
        }
        $join = ' JOIN '.$_tableRef.' ON '.$on;
        return $join;
    }
    // String $by = 'column1, column2, ...'
    // Array $by = ['column1', 'column2', ...]
    public static function group($by){
        $groupQuery = ' GROUP BY ';
        if(is_string($by)){
            return $groupQuery.$by;
        }else if(is_array($by)){
            return $groupQuery. implode(',', $by);
        }
        return STRING_EMPTY;
    }
    public static function like_separator($val, $operand = '=')
    {
        return (strstr($val,'%') != STRING_EMPTY) ? ' LIKE ' : ' '.$operand.' ';
    }
    public static function order_by($column, $type){
        $col = NULL;
        if(is_string($column)){
            $col = $column;
        }else if(is_array($column)){
            $count = count($column);
            for ($i=0; $i < $count; $i++) {
                $col .= $column[$i]; 
                if($i < $count){
                    $col .= ',';
                }
            }
        }
        return ' ORDER BY ' . $col . ' ' . $type;
    }
    public static function query($__q_or_t, $__wh = STRING_EMPTY, $__bool = 'AND')
    {
        $__dt = STRING_EMPTY;
        $__values = array();
        if (is_array($__wh)) {
            $index = 0;
            foreach ($__wh as $key => $value) {
                $separator = (strstr($value,'%') != STRING_EMPTY) ? 'LIKE' : '=';
                // $__dt .= "`$key` $separator '$value'". 
                $__dt .= string_quote_query($key)." $separator ?". 
                (($index == count($__wh) - 1) ? STRING_EMPTY : " $__bool " ); // Condition AND/OR
                $__values[] = $value;
                $index++;
            }
            $__wh = $__dt;
        }
        if (!(string_contains('select',$__q_or_t)) && !(string_contains('from',$__q_or_t))) {
           $__q_or_t = 'SELECT * FROM '.string_quote_query($__q_or_t)
                       .str_allow(!string_empty($__wh),' WHERE '.$__wh); // use WHERE when where is not null
        }
        return array(
            'query' => $__q_or_t,
            'values' => $__values
        );
    }
    public static function prepare_insert($tableName,$_dt_arr)
    {
        $_keys = STRING_EMPTY;
        $_values = [];
        $_seeds = STRING_EMPTY;
        $_q = 'insert into '.string_quote_query($tableName);
        $i = 0;
        // parameter
        $_params = [];
        $bIsBatchInsert = FALSE;
        foreach ($_dt_arr as $item) {
            $bIsBatchInsert = (is_array($item));
            break;
        }
        $_params = $bIsBatchInsert ? $_dt_arr : [$_dt_arr];
        // count params insert data
        $len_params = count($_params);
        for ($i = 0; $i < $len_params; $i++) {
            $param = $_params[$i]; // DataInsert[i]
            $len_param = count($param);
            $last_param = $param[array_key_last($param)];
            // loop for child
            
            if($i >= 1 && $i < $len_params)
                $_seeds .= ',';

            if($i == 0)
                $_q .= '(';
            $_seeds .= '(';
            $j = 0;
            foreach ($param as $key => $value) {
                if($i == 0)
                    $_q .= string_quote_query($key).str_allow($last_param != $value ,',');
                $_values[] = $value;
                $_seeds .= str_allow($j > 0 , ',').' ?';

                $j++;
            }
            $_seeds .= ')';
            if($i == 0)
                $_q .= ')'. str_allow($i < $len_param && $i > 1 , ',');
        }
        $_q .= 'values'.$_seeds;
        return ['query' => $_q, 'values' => $_values];
    }
    public static function get_key_values($__dt_arr)
    {
        $keys = STRING_EMPTY;$values = STRING_EMPTY;
        $index = 0;
        foreach ($__dt_arr as $key => $value) {
            $dot = ($index == (count($__dt_arr) - 1) ? STRING_EMPTY : ',' );
            $keys .= string_quote_query($key).$dot;
            dsSystem::fill_text($value);
            $values .= "'$value'".$dot;
            $index++;
        }
        return array('keys' => $keys, 'values' => $values);
    }
    public static function delete($__table, $__wh, $__bool)
    {
        $__q['query'] = "DELETE FROM `$__table`";
        $__value = [];
        if (is_string($__wh)) {
            // $__wh = dsCore::get_connection()->escape_string($__wh);
            if (!string_empty_or_null($__wh)) {
                $__q['query'] .= str_allow(!string_contains('WHERE', substr($__wh, 0, 7)), ' WHERE ').$__wh;
            }
        }else if (is_array($__wh)) {
            $index = 0;
            $__q['query'] .= ' WHERE ';
            foreach ($__wh as $key => $value) {
                // $value = dsCore::get_connection()->escape_string($value);
                $__value[] = $value;
                $__q['query'] .= ' '.string_quote_query($key).' = ? '.
                (($index == count($__wh) - 1) ? STRING_EMPTY : 'AND' );
                $index++;
            }
        }
        $__q['values'] = $__value;
        return $__q;
    }
    public static function update($__table, $__dt, $__wh)
    {
        $__get_data_set = STRING_EMPTY;
        $__get_data_where = STRING_EMPTY;
        $index = 0;
        $_values = [];
        foreach ($__dt as $key => $value) {
            dsSystem::fill_text($value);
            $_values[] = $value;
            $__get_data_set .= string_quote_query($key).' = ?'.($index == (count($__dt) - 1) ? "" : "," );
            $index++;
        }
        $index = 0;
        if (is_string($__wh)) {
            if ($__wh !== STRING_EMPTY) {
                $__get_data_where .= $__wh;
            }
        }else if(is_array($__wh)){
            foreach ($__wh as $key => $value) {
                dsSystem::fill_text($value);
                $_values[] = $value;
                $__get_data_where .= string_quote_query($key).' = ?'.
                ($index == (count($__wh) - 1) ? STRING_EMPTY : ' AND ' );
                $index++;
            }
        }
        $__q['query'] = 'UPDATE '.string_quote_query($__table).'
                SET '.$__get_data_set.'
                WHERE '.$__get_data_where;
        $__q['values'] = $_values;
        return $__q;
    }
}