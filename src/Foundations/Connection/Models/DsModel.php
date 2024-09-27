<?php
/*
Model:
- include simple method for build query and execute it directly.
 */

namespace Ds\Foundations\Connection\Models;

use ArrayAccess;
use Ds\Foundations\Connection\DatabaseProvider;
use Ds\Foundations\Connection\Db;

class DsModel extends \ArrayIterator implements ArrayAccess
{
    /**
     * @var Db $connection
     */
    protected Db $connection;
    protected $primaryKey = null;
    public $table = null;
    protected $fillable = null;

    public function __construct()
    {
        if ($this->table == null) {
            $this->table = str_replace('Model', '', get_called_class());
            $this->table = substr($this->table, strrpos($this->table, '\\') + 1);
            $this->table = strtolower($this->table);
        }
        $this->connection = DatabaseProvider::getConnection();
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
     * @return DsModel
     */
    public static function _select($columns = null, $from = null)
    {
        $classname = get_called_class();
        /**
         * @var DsModel
         */
        $obj = new $classname;
        if (is_null($columns)) {
            $columns = $obj->table;
        }
        return $obj->select($columns, $from ?? $obj->table);
    }
    protected function select($columns = null, $from = null)
    {
        $this->connection = $this->connection->select($columns, $from);
        return $this;
    }
    public function query($syntax)
    {
        $this->connection = $this->connection->query($syntax);
        return $this;
    }
    public function getQuery()
    {
        return $this->connection->getQuery();
    }
    public function distinct($columns, $from = null)
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
    public function  and ($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
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
    public function  or ($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
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
    protected function where($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        if (!$this->connection->isQueryTypeReady()) {
            $this->connection = $this->connection->select($this->table);
        }
        $this->connection = $this->connection->where($arg1, $arg2, $arg3, $arg4);
        return $this;
    }
    public function whereIn($column1, $arrValues)
    {
        $this->connection = $this->connection->whereIn($column1, $arrValues);
        return $this;
    }
    // called when Model::method() was called
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this, $method), $arguments);
    }
    public static function __callStatic($method, $arguments)
    {
        switch ($method) {
            case 'where':
                return call_user_func_array(array(self::initiateClass(), $method), $arguments);
            case 'save':
                return self::initiateClass()->save(...$arguments);
            case 'update':
                return self::initiateClass()->update(...$arguments);
            case 'like':
                return self::initiateClass()->like(...$arguments);
            case 'select':
                return self::initiateClass()->select(...$arguments);
            case 'first':{
                    $obj = self::initiateClass();
                    return $obj->select($obj->table)->first(...$arguments);

                }

            default:
                return self::{$method}(...$arguments); //call_user_func(class . $method, ...$arguments);
                break;
        }
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
    /*
    Example
    $onDuplicateKeyUpdate = function($row) {
    return ['id' => $row['id]];
    }
    query : ... ON DUPLICATE KEY UPDATE id=row[id]
     */
    public function insert_bulk($tableName, $arrayData, $onDuplicateKeyUpdate = null)
    {
        if (count($arrayData) == 0) {
            return;
        }

        if (is_bool($onDuplicateKeyUpdate) && $onDuplicateKeyUpdate) {
            $columns = array_keys($arrayData[0]);
            $onDuplicateKeyUpdate = [];
            foreach ($columns as $column) {
                $onDuplicateKeyUpdate[$column] = $column;
            }
        }
        $this->connection->bulkInsertObject($tableName, $arrayData, $onDuplicateKeyUpdate)->execute();
    }
    public function update($tableName, $data = null)
    {
        $db = $this->connection->update($tableName, $data);

        if ($this->primaryKey == null) {
            return $db;
        }

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
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        $className = get_called_class();
        /**
         * @var DsModel
         */
        $obj = new $className();
        $tableName = $obj->table;
        if (count($columns) > 0) {
            return $obj->select($columns, $tableName)->get_object();
        }

        return $obj->select($tableName)->get_object();
    }
    public function get_object()
    {
        // $this->dataResult = $this->connection->get_object();
        // $this->position = 0;
        return $this->connection->get_object();
    }
    public function row()
    {
        return $this->connection->get_row_object();
    }
    protected function first()
    {
        return $this[0];
    }
    public static function initiateClass()
    {
        $className = get_called_class();
        return new $className();
    }
    public static function last($columns = [])
    {
        $className = get_called_class();
        $obj = self::initiateClass();
        $tableName = $obj->table;
        if (count($columns) > 0) {
            return $obj->select($columns, $tableName)->desc('id')->limit(1)->row();
        }

        return $obj->select($tableName)->desc('id')->limit(1)->row();
    }

    public static function find($id, $columns = '*')
    {
        return self::findBy('id', $id, $columns);
        // $className = get_called_class();
        // $obj = new $className();
        // $tableName = $obj->table;
        // return $obj->select($columns, $tableName)->where('id', $id)->row();
    }
    public static function findBy($columnName, $columnValue, $columns = '*')
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($columns, $tableName)->where($columnName, $columnValue)->row();
    }
    public static function findIsNull($columnName, $columns = '*')
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($columns, $tableName)->isNull($columnName)->row();
    }
    public static function findIsNotNull($columnName, $columns = '*')
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($columns, $tableName)->isNotNull($columnName)->row();
    }
    public static function findsBy($columnName, $columnValue, $columns = '*')
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($columns, $tableName)->where($columnName, $columnValue)->get_object();
    }
    public static function findsWhere(
        $arg1,
        $arg2 = null,
        $arg3 = null,
        $arg4 = null
    ) {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($tableName)->where($arg1, $arg2, $arg3, $arg4)->get_object();
    }
    public static function findsIsNull($columnName, $columns = '*')
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($columns, $tableName)->isNull($columnName)->get_object();
    }
    public static function findsIsNotNull($columnName, $columns = '*')
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($columns, $tableName)->isNotNull($columnName)->get_object();
    }
    public static function exist($columnName, $columnValue = null)
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        return $obj->select($tableName)->where($columnName, $columnValue)->exist();
    }
    private static function bulkSave(DsModel $obj, $arrays)
    {
        try {
            $filledData = [];
            foreach ($arrays as $data) {
                $filledData[] = $obj->filledFields($data);
            }
            $obj->insert_bulk($obj->table, $filledData);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    private static function processSave($objModel, $data)
    {
        $tableName = $objModel->table;
        $data = (object) $data;
        $id = $data->id ?? 0;
        $isExist = $objModel->select($tableName)->where('id', $id)->get_exist();
        $data = (array) $data;

        $filledValue = $objModel->filledFields($data);
        if ($isExist) {
            $objModel->update($tableName, $filledValue)->where('id', $id)->execute();
            return $id;
        } else {
            return $objModel->insert($tableName, $filledValue);
        }
    }
    public static function save($data)
    {
        try {
            $className = get_called_class();
            $obj = new $className();
            if (is_array($data)) {
                if (is_numeric(array_keys($data)[0])) {
                    return self::bulkSave($obj, $data);
                }
            }
            $data = (object) $data;
            return self::processSave($obj, $data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public static function size($where = null): int
    {
        try {
            $className = get_called_class();
            $obj = new $className();
            $db = $obj->select(Db::raw('SUM(1) total'), $obj->table);
            if ($where != null) {
                $db->where($where);
            }
            $data = $db->row();

            return $data->total ?? 0;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function filledFields($fields)
    {
        $data = [];
        foreach ($this->fillable as $fillable) {
            if (isset($fields[$fillable])) {
                $data[$fillable] = $fields[$fillable];
            }
        }
        return $data;
    }
    public static function remove(int | array $idWhere)
    {
        $className = get_called_class();
        $obj = new $className();
        $tableName = $obj->table;
        if (is_array($idWhere)) {
            $obj->delete(strtolower($tableName))->where($idWhere)->execute();
        } else {
            $obj->delete(strtolower($tableName))->where('id', $idWhere)->execute();
        }
    }

    private $dataResult = null;
    private $position = 0;

    private function validate()
    {
        if ($this->dataResult == null) {
            $this->dataResult = $this->get_object();
        }
    }

    public function offsetSet($offset, $value): void
    {
        $this->validate();
        if (is_null($offset)) {
            $this->dataResult[] = $value;
        } else {
            $this->dataResult[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        $this->validate();
        return isset($this->dataResult[$offset]);
    }

    public function offsetUnset($offset): void
    {
        $this->validate();
        unset($this->dataResult[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        $this->validate();
        return isset($this->dataResult[$offset]) ? $this->dataResult[$offset] : null;
    }
    // Return the current element
    public function current(): mixed
    {
        $this->validate();
        return $this->dataResult[$this->position];
    }

    // Return the current key
    public function key(): int
    {
        $this->validate();
        return $this->position;
    }

    // Move forward to the next element
    public function next(): void
    {
        ++$this->position;
    }

    // Rewind the iterator to the first element
    public function rewind(): void
    {
        $this->position = 0;
    }

    // Checks if the current position is valid
    public function valid(): bool
    {
        $this->validate();
        return isset($this->dataResult[$this->position]);
    }

}
