<?php
/*
    Model:
    - include simple method for build query and execute it directly.
*/

namespace Ds\Foundations\Connection\Models;

use Ds\Foundations\Common\Func;
use Ds\Foundations\Connection\DatabaseProvider;
use Ds\Foundations\Connection\Db;
use Ds\Helper\Date;

class DsModel
{
    /**
     * @var Db $connection
     */
    protected $connection;
    protected $primaryKey = NULL;
    public $table = NULL;

    public function __construct()
    {
        if ($this->table == NULL) {
            $this->table = str_replace('Model', '', get_called_class());
            $this->table = substr($this->table, strrpos($this->table, '\\') + 1);
            $this->table = strtolower($this->table);
        }
        $this->connection = DatabaseProvider::$db;
    }
    /**
     * Generating select query
     * example :
     * 
     * ```php
     * select('mytable')
     * // or
     * select('column1, column2, ...', 'mytable')
     * // or
     * select([ 'column1', ... ], 'mytable')
     * // or
     * select([
     *      'column1' => 'alias1', 
     *      'column2' => 'alias2',
     *      ...
     * ], 'mytable')
     * ```
     * 
     * @param  string|string[] $arg1 Table name or columns name
     * @param  string|string[] $arg2 will be table name
     * @return Db
     */
    public static function select($columns = null, $from = NULL)
    {
        $classname = get_called_class();
        $obj = new $classname;
        if(is_null($columns))
            $columns = $obj->table;
        return $obj->connection->select($columns, $from);
        // return $this;
    }
    public function query($syntax)
    {
        return $this->connection->query($syntax);
    }
    public function getQuery()
    {
        return $this->connection->getQuery();
    }
    public function distinct($columns, $from = NULL)
    {
        $newDb = new Db();
        return $newDb->distinct($columns, $from);
    }
    // String Columns = 'columnGroup1, columnGroup2'
    // Array Columns = ['columnGroup1', 'columnGroup2']
    public function groupBy($columns)
    {
        $this->connection = $this->connection->groupBy($columns);
        return $this;
    }
    /**
     * asc
     *
     * @param  string|string[] $column_name
     * @return \DsModel
     */
    public function asc($column_name)
    {
        $this->connection = $this->connection->asc($column_name);
        return $this;
    }
    public function limit($length = 1, $start = 0)
    {
        $this->connection = $this->connection->limit($length, $start);
        return $this;
    }
    public function desc($column_name)
    {
        $this->connection = $this->connection->desc($column_name);
        return $this;
    }
    /**
     * join
     * ```php
     * ->join('table1', 'table1.column1', 'table2.column2')
     * // INNER JOIN table1 tbl1 ON tbl1.column1 = tbl2.column
     * ->join('table1 tbl1', 'tbl1.column1', 'tbl2.column')
     * // INNER JOIN (SELECT * FROM table2) tbl2 
     * //            ON tbl1.column1=tbl2.column
     * ->join(fn($db) => $db->select('table2'),
     * 'tbl2', 'tbl1.column1', 'tbl2.column')
     * ```
     * @param  \Closure|string $arg1
     * @param  string $arg2
     * @param  string $arg3
     * @param  string $arg4
     * @return DsModel
     */
    public function join($arg1, $arg2, $arg3, $arg4 = null, $arg5 = null)
    {
        $this->connection = $this->connection->join($arg1, $arg2, $arg3, $arg4, $arg5);
        return $this;
    }
    /**
     * Left join
     * ```php
     * ->leftJoin('table2', 'table2.column1', 'table1.column1')
     * // OR
     * ->leftJoin('table2 a', 'a.column1', 'table1.column1')
     * // OR
     * ->leftJoin(fn($db) => $db->select('table3')->where(....), 
     * 'a', 'a.column1', 'table1.column1')
     * ```
     *
     * @param  \Closure|string $arg1
     * @param  string $arg2
     * @param  string $arg3
     * @param  string $arg4
     * @return DsModel
     */
    public function leftJoin($arg1, $arg2, $arg3, $arg4 = null)
    {
        $this->connection = $this->connection->leftJoin($arg1, $arg2, $arg3, $arg4);
        return $this;
    }
    public function having($columns)
    {
        $this->connection = $this->connection->having($columns);
        return $this;
    }
    /**
     * where and clause
     *
     * @param  string|array|callback $arg1
     * @param  mixed $arg2 (optional)
     * @param  mixed $arg3 (optional)
     * @param  mixed $arg4 (optional)
     * @return DsModel
     */
    public function and($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        $this->connection = $this->connection->and($arg1, $arg2, $arg3, $arg4);
        return $this;
    }
    /**
     * Where OR
     * ```php
     * ->or('column1', 'value1')
     * // With sub where
     * ->or(fn($w) => $w->or('column1', 'value1')->or('column'))
     * // With custom operator
     * ->or('column1','>', 'value2')
     * // With custom value example for MD5 method for value
     * ->or('column1','value2', fn($v) => "MD5($v)")
     * ```
     *
     * @param  mixed $arg1
     * @param  mixed $arg2
     * @param  mixed $arg3
     * @param  mixed $arg4
     * @return DsModel
     */
    public function or($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        $this->connection = $this->connection->or($arg1, $arg2, $arg3, $arg4);
        return $this;
    }
    /**
     * Where
     * ```php
     * ->where('column1', 'value1')
     * // With sub where
     * ->where(fn($w) => $w->or('column1', 'value1')->or('column'))
     * // With custom operator
     * ->where('column1','>', 'value2')
     * // With custom value example for MD5 method for value
     * ->where('column1','value2', fn($v) => "MD5($v)")
     * // with array
     * ->where([
     *    'column1' => 'value1',
     *    'column2' => 'value2',
     *    [
     *      'column4' => 'value4',
     *      'column5' => 'value5',
     *    ],
     *    'OR', 'column6' => 'value6'
     * ])
     * ```
     *
     * @param  mixed $arg1
     * @param  mixed $arg2
     * @param  mixed $arg3
     * @param  mixed $arg4
     * @return DsModel
     */
    public function where($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        $this->connection = $this->connection->where($arg1, $arg2, $arg3, $arg4);
        return $this;
    }
    // // where x like y
    /**
     * Where
     * ```php
     * ->where('column1', 'value1')
     * // With sub where
     * ->where(fn($w) => $w->or('column1', 'value1')->or('column'))
     * // With custom operator
     * ->where('column1','>', 'value2')
     * // With custom value example for MD5 method for value
     * ->where('column1','value2', fn($v) => "MD5($v)")
     * ```
     *
     * @param  mixed $arg1
     * @param  mixed $arg2
     * @param  mixed $arg3
     * @param  mixed $arg4
     * @return DsModel
     */
    public function like($column, $value)
    {
        $this->connection = $this->where($column, ' LIKE ', $value);
        return $this;
    }
    /**
     * orLike
     *
     * @param  mixed $column
     * @param  mixed $value
     * @return DsModel
     */
    public function orLike($column, $value)
    {
        $this->connection = $this->or($column, ' LIKE ', $value);
        return $this;
    }
    // // where x = y
    public function equal($column, $value)
    {
        return $this->and($column, ' = ', $value);
    }
    // // where x != y
    public function not_equal($column, $value)
    {
        return $this->and($column, ' != ', $value);
    }
    public function insert($tableName, $data = null)
    {
        return $this->connection->insert($tableName, $data)->execute();
    }
    public function insert_bulk_array($tableName, $columns, $arrayData)
    {
        $this->connection->bulkInsertArray($tableName, $columns, $arrayData)->execute();
    }
    public function insert_bulk($tableName, $arrayData)
    {
        $this->connection->bulkInsertObject($tableName, $arrayData)->execute();
    }
    public function update($tableName, $data = null)
    {
        $db = $this->connection->update($tableName, $data);

        if ($this->primaryKey == null)
            return $db;

        return $db->where($this->primaryKey, isset($data->id) ? $data->id : $data[$this->primaryKey], '=');
    }
    // // where x > y
    // public function greater($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    // {
    //     return $this->where_root($arg1, $arg2, ' > ', $arg3, 'AND', $isSeparate);
    // }
    // // where x >= y
    // public function greater_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    // {
    //     return $this->where_root($arg1, $arg2, ' >= ', $arg3, 'AND', $isSeparate);
    // }
    // // where x < y
    // public function lower($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    // {
    //     return $this->where_root($arg1, $arg2, ' < ', $arg3, 'AND', $isSeparate);
    // }
    // // where x <= y
    // public function lower_equal($arg1, $arg2 = STRING_EMPTY, $arg3 = 'AND', $isSeparate = false)
    // {
    //     return $this->where_root($arg1, $arg2, ' <= ', $arg3, 'AND', $isSeparate);
    // }
    public function delete($tableName)
    {
        return $this->connection->delete($tableName);
    }
    public static function all($columns = [])
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        if (count($columns) > 0)
            return $obj->select($columns, $tableName)->get_object();
        return $obj->select($tableName)->get_object();
    }
    public static function last($columns = [])
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        if (count($columns) > 0)
            return $obj->select($columns, $tableName)->desc('id')->limit(1)->get_row_object();
        return $obj->select($tableName)->desc('id')->limit(1)->get_row_object();
    }

    public static function find($id, $columns = '*')
    {
        return self::findBy('id', $id, $columns);
        // $className = get_called_class();
        // $obj = new $className();
        // $tableName = $obj->table;
        // return $obj->select($columns, $tableName)->where('id', $id)->get_row_object();
    }
    public static function findBy($columnName, $columnValue, $columns = '*'){
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($columns, $tableName)->where($columnName, $columnValue)->get_row_object();
    }
    public static function exist($columnName, $columnValue){
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($tableName)->where($columnName, $columnValue)->exist();
    }
    public static function save($data, $return = false)
    {
        $data = (object)$data;
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        $id = $data->id ?? 0;
        $isExist = $obj->select($tableName)->where('id', $id)->get_exist();
        $data = (array) $data;
        if ($isExist) {
            $obj->update($tableName, $data)->where('id', $id)->execute();
        } else {
            $obj->insert($tableName, $data);
        }
        $includeTimestamp = isset($data['timestamp']);
        if($includeTimestamp || $return){
            if(!$includeTimestamp){
                $data['timestamp'] = Date::timestamp();
            }
            return $obj->select($tableName)->where('timestamp', $data['timestamp'])->get_row_assoc();
        }
    }
    public static function remove($id)
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        $obj->delete(strtolower($tableName))->where('id', $id)->execute();
    }
}
