<?php
/*
Model:
- include simple method for build query and execute it directly.
 */

namespace Ds\Foundations\Connection\Models;

use Ds\Foundations\Common\Collection;
use Ds\Foundations\Connection\DatabaseProvider;
use Ds\Foundations\Connection\Db;
use Ds\Foundations\Network\Request;

class DsModel extends Collection
{
    /**
     * @var Db $connection
     */
    private Db $connection;
    protected $primaryKey = null;
    public $table = null;
    protected $fillable = null;

    public function __construct()
    {
        if ($this->table === null) {
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
    public static function _select($columns = null, $from = null): DsModel
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
    protected function select($columns = null, $from = null): DsModel
    {
        $this->connection = $this->connection->select($columns, $from);
        return $this;
    }
    public function query($syntax): DsModel
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
    /**
     * Group query
     * @param array|string $columns
     * @return DsModel
     */
    public function groupBy($columns): DsModel
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
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $arguments);
        } else {
            throw new \BadMethodCallException("Method '$method' not found in " . get_called_class() . " class");
        }
    }
    public static function __callStatic($method, $arguments)
    {
        try {
            if (in_array($method, [
                'where',
                'save',
                'update',
                'like',
                'select',
                'size',
            ])) {
                return call_user_func_array(array(self::initiateClass(), $method), $arguments);
            }
            switch ($method) {
                case 'first':{
                        $obj = self::initiateClass();
                        return $obj->select($obj->table)->first(...$arguments);
                    }

                default:{
                        return self::{$method}(...$arguments);
                    }
            }
        } catch (\Throwable $th) {
            throw $th;
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

        if ($this->primaryKey === null) {
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
    public static function all($columns = []): DsModel
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
            return $obj->select($columns, $tableName);
        }

        return $obj->select($tableName);
    }
    public function get_object()
    {
        // $this->dataResult = $this->connection->get_object();
        // $this->position = 0;
        return $this->connection->get_object();
    }
    public function get_array()
    {
        // $this->dataResult = $this->connection->get_object();
        // $this->position = 0;
        return $this->connection->get_assoc();
    }

    public function row()
    {
        return $this->connection->get_row_object();
    }
    public function first( ? callable $callback = null, $default = null)
    {
        return parent::first($callback, $default);
    }
    public static function initiateClass()
    {
        $className = get_called_class();
        return new $className();
    }
    public function last( ? callable $callback = null, $default = null)
    {
        return parent::last($callback, $default);
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
    protected function exist()
    {
        return $this->connection->get_exist();
        // $className = get_called_class();
        // $obj = new $className();
        // $tableName = $obj->table;
        // return $obj->select($tableName)->where($columnName, $columnValue)->exist();
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
        $isExist = $objModel->select($tableName)->where('id', $id)->exist();
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
    private function size($where = null) : int
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

    protected function paginate($limit = 10)
    {
        $request = new Request();
        $url = $_SERVER['PATH_INFO'];
        $currentPage = (int) ($request->page ?? 1);
        $start = ($currentPage - 1) * $limit;
        $nextPage = $currentPage + 1;
        $prevPage = $currentPage - 1;
        $size = $this->size();
        $lastPage = ceil($size / $limit);
        $nextPage = $currentPage == $lastPage ? null : ($currentPage + 1);

        $data = [
            'data' => $this->limit($limit, $start),
            'links' => [
                'prev' => $currentPage == 1 ? null : ($url . '?page=' . $prevPage),
                'next' => $nextPage != null ? ($url . '?page=' . $nextPage) : null,
                'first' => $url . '?page=1',
                'last' => $lastPage === null ? null : ($url . '?page=' . $lastPage),
            ],
            'meta' => [
                'current_page' => $currentPage,
                'from' => $start,
                'last_page' => $lastPage,
                'path' => $url,
                'per_page' => $limit,
                'to' => $size,
                'total' => $size,
            ],
        ];
        return $data;
    }
    private $readyExecute = true;
    public function validate()
    {
        if ($this->readyExecute) {
            $this->array = $this->get_object();
            $this->readyExecute = false;
        }
    }

    // Return the current key
    public function key() : int
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
        $this->validate();
        $this->position = 0;
    }

    public function count(): int
    {
        $this->validate();
        return $this->size();
    }

    // Checks if the current position is valid
    public function valid(): bool
    {
        $this->validate();
        return parent::valid();
    }
    public function __toString(){
        $this->validate();
        return json_encode($this->dataResult);
    }    // ------------------- Collection Override
}
