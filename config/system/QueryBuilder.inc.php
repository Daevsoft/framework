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
    
    public static function select($arg1, $arg2){
        $q = STRING_EMPTY;
        if ($arg2 != STRING_EMPTY) {
            $col = STRING_EMPTY;
            if(is_array($arg1)){
                $len = count($arg1);
                $i = 1;
                foreach ($arg1 as $column => $as) {
                    if($i <= $len && $i > 1)
                        $col .= ',';
                    $col .= $column . ' AS ' . $as;
                    $i++;
                }
            }
            if(is_string($arg1)){
                $col = $arg1;
            }
            $q = 'SELECT '.$col.' FROM '.$arg2;
        }else{
            $q = 'SELECT * FROM '.$arg1;
        }
        return $q;
    }
    public static function join($_table, $onCondition)
    {
        $on = STRING_EMPTY;
        // Create " 'tableName' asName "
        $_tableRef = $_table;
        // $_tableExp = 'TableName' asName
        if(is_array($onCondition)){
            $len = count($onCondition);
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
    public static function like_separator($val, $operand = '=')
    {
        return (strstr($val,'%') != STRING_EMPTY) ? ' LIKE ' : ' '.$operand.' ';
    }
    public static function query($__q_or_t, $__wh = '', $__bool = 'AND')
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
        if ((string_contains('select',$__q_or_t)) && (string_contains('from',$__q_or_t))) {
           $__q_or_t = 'SELECT * FROM '.string_quote_query($__q_or_t)
                       .(($__wh != STRING_EMPTY) ? ' WHERE '.$__wh : ''); // use WHERE when where is not null
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
        $_q = 'insert into '.string_quote_query($tableName).'(';
        $i = 0;
        foreach ($_dt_arr as $key => $value) {
            $i++;
            $boolComma = ($i != count($_dt_arr)) ? ',' : '';
            $_q .= string_quote_query($key).$boolComma;
            $_values[] = $value;
            $_seeds .= '?'.$boolComma;
        }
        $_q .= ')values('.$_seeds.');';
        return ['query' => $_q, 'values' => $_values];
    }
    public static function get_key_values($__dt_arr)
    {
        $keys = STRING_EMPTY;$values = STRING_EMPTY;
        $index = 0;
        foreach ($__dt_arr as $key => $value) {
            $dot = ($index == (count($__dt_arr) - 1) ? STRING_EMPTY : ',' );
            $keys .= string_quote_query($key).$dot;
            $value = fill_text($value);
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
            if ($__wh != STRING_EMPTY) {
                $__q .= ' WHERE '.$__wh;
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
            $_values[] = dsSystem::fill_text($value);
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
                $_values[] = dsSystem::fill_text($value);
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