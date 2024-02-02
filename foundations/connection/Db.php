<?php

namespace Ds\Foundations\Connection;

use Ds\Foundations\Connection\Arch\QueryCommon;
use Ds\Foundations\Connection\Arch\Sets\Join;
use Ds\Foundations\Connection\Arch\Sets\Set;
use Ds\Foundations\Connection\Arch\Sets\SetWhere;
use Ds\Foundations\Connection\Arch\Sets\SetWhereRaw;
use Ds\Foundations\Exceptions\dsException;
use Ds\Foundations\Provider;
use Ds\Helper\Str;
use Exception;
use Closure;
use Ds\Foundations\Config\Env;
use PDO;
use PDOException;

define('SQLSERV', 'sqlserv');
define('MYSQL', 'mysql');
define('POSTGRE', 'pgsql');
define('SPACE', ' ');

class Db extends QueryCommon
{
    private $driver;
    private $host;
    private $username;
    private $password;
    private $database;
    private $ssl_cert;
    private $ssl_verify;
    public static $module_name = '__db_class';
    private $columns;
    protected $primaryKey = 'id';

    /**
     * connection
     *
     * @var \PDO
     */
    private $connection;
    /**
     * statement
     *
     * @var PDOStatement
     */
    private $statement;
    private $transaction;
    /**
     * @var array<SetWhere>
     */
    private $whereValues;
    /**
     * @var array<Join>
     */
    private $joinValues;
    /**
     * @var array<Set>
     */
    private $additionalParameters;
    /**
     * @var Db
     */
    /**
     * sql query
     *
     * @var string
     */
    private $query = STRING_EMPTY;
    /**
     * parentDb
     *
     * @var Db
     */
    private $parentDb;
    /**
     * @var int 
     */
    public static $identity_increment;
    /** 
     * @var int 
     */
    private $identity = 0;

    /**
     * queryType inform that query is iterate or not
     *
     * @var string
     */
    private $queryType = null;

    /**
     * @var SqlModel
     */
    private $sqlModel;

    /**
     * @var int
     * @return void
     */
    public function __construct()
    {
        $this->setupProvider();
        $this->identity = Db::$identity_increment;
        Db::$identity_increment++;
        $this->sqlModel = new SqlModel($this, $this->quotSql, $this->endQuotSql, $this->bindSymbol);
        $this->getConnection();
    }
    /**
     * @param  string $provider
     * @return void
     */
    public function setupProvider()
    {
        $this->driver = Env::get('DB_DRIVER');
        $this->host = Env::get('DB_HOST');
        $this->username = Env::get('DB_USERNAME');
        $this->password = Env::get('DB_PASSWORD');
        $this->database = Env::get('DB_NAME');
        $this->ssl_cert = Env::get('SSL_CERT');
        $this->ssl_verify = Env::get('SSL_VERIFY', false);
        
        try {
            if(empty($this->database)){
                throw new dsException('Database not found!');
            }
            if (
                $this->driver == MYSQL ||
                $this->driver == POSTGRE ||
                $this->driver == SQLSERV
            ) {
                $this->setup();
            } else {
                throw new Exception("Provider not supported");
            }
        } catch (dsException $th) {
            //throw $th;
        }
    }
    
    /**
     * Get options for pdo connection
     *
     * @return array|null
     */
    private function getDbOptions():array|null{
        if($this->ssl_cert == null) return null;
        return array(
            PDO::MYSQL_ATTR_SSL_CA => $this->ssl_cert,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        );
    }
    /**
     * Get pdo connection instance
     *
     * @return PDO
     */
    public function getConnection()
    {
        try {
            $con_string = $this->getHostConnection();
            $options = $this->getDbOptions();
            if (is_null($this->connection) && $con_string != null) {
                $this->connection = new PDO($con_string, $this->username, $this->password, $options);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            // return PDO instance
            return $this->connection;
        } catch (PDOException $ex) {
            $ex = new dsException($ex, __FILE__);
            $ex->show_exception(true);
            die();
        }
    }
    /**
     * Generate and get connection string from configuration
     *
     * @return string
     */
    private function getHostConnection()
    {
        $_db_key = $_host_key = STRING_EMPTY;
        if(Str::empty($this->driver) || Str::empty($this->database)) return null;
        switch ($this->driver) {
                // MySql Provider
            case MYSQL:
                $_db_key = 'dbname';
                $_host_key = 'host';
                $this->setBehavior('`', '`');
                break;
                // SQL Server Provider
            case SQLSERV:
                $_db_key = 'Database';
                $_host_key = 'Server';
                $this->setBehavior('[', ']');
                break;
        }
        return $this->driver . ':' . $_host_key . '=' .
            $this->host . ';' . $_db_key . '=' .
            $this->database . ';';
    }

    private function setup()
    {
        $this->getConnection();
        $this->clear();
    }
    /**
     * addParameter
     *
     * @param  mixed $set
     * @return Db
     */
    public function addParameter($set)
    {
        if ($set instanceof SetWhereRaw && $set->IsRaw) return $this;
        $this->additionalParameters[] = $set;
        return $this;
    }
    /**
     * Set query into connection
     *
     * @param  string $sql
     * @return Db
     */
    public function query($sql)
    {
        $this->query = $sql;
        return $this;
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
    public function select($arg1, $arg2 = null)
    {
        $db = new Db();
        $db->queryType = self::SELECT;

        if (is_string($arg1) && (is_string($arg2) || is_null($arg2))) {
            if ($arg2 == null) {
                return $db->select1($arg1);
            } else {
                $db->columns = [$arg1];
                return $db->select2($arg1, $arg2);
            }
        } else {
            $db->columns = $arg1;
            return $db->select3($arg1, $arg2);
        }
    }
    private function checkDistinctQuery()
    {
        if ($this->queryType == self::DISTINCT) {
            $this->query = 'SELECT DISTINCT ' . substr($this->query, strpos($this->query, ' ') + 1);
        }
    }
    public function distinct($arg1, $arg2)
    {
        $db = $this->select($arg1, $arg2);
        $db->queryType = self::DISTINCT;
        return $db;
    }
    public function getQueryType()
    {
        return $this->queryType;
    }
    /**
     * select with table name
     *
     * @param  string $tableName
     * @return Db
     */
    private function select1($tableName)
    {
        $sql = "SELECT * FROM " . $this->WrapQuot($tableName);
        return $this->query($sql);
    }
    /**
     * select with columns and table name
     *
     * @param  string $columns
     * @param  string $table
     * @return Db
     */
    private function select2(string $columns, $table)
    {
        $isRaw = $this->checkRaw($columns);
        $fromTable = $this->getTableNameOrQuery($table);
        $tableAlias = '';
        $columnAlias = '';
        if (is_object($table)) {
            $tableAlias = ' x';
            if ($isRaw === false)
                $columnAlias = 'x.';
        }
        if (!$isRaw) $columns = $this->WrapQuot($columns);

        $sql = 'SELECT ' . $columnAlias . $columns . ' FROM ' . $fromTable . $tableAlias;
        return $this->query($sql);
    }
    public function getSelectedColumns()
    {
        $columns = [];
        foreach ($this->columns as $key => $value) {
            $column = $value;
            $spaceIndex = strrpos($column, ' ');
            $column = ($spaceIndex !== false) ? substr($column, $spaceIndex + 1) : $column;
            $dotIndex = strpos($column, '.');
            if ($dotIndex !== false) $column = substr($column, $dotIndex + 1);
            $columns[] = Str::replace($column, '`');
        }
        return $columns;
    }
    private function getTableNameOrQuery($tableOrDb)
    {
        $fromTable = null;
        if (is_string($tableOrDb)) {
            $fromTable = $this->WrapQuot($tableOrDb);
        } else if (is_object($tableOrDb) && $tableOrDb instanceof Db) {
            // TODO alias table
            $this->copyProperties($tableOrDb);
            $fromTable = '(' . $tableOrDb->getQuery() . ')';
        }
        return $fromTable;
    }
    public function copyProperties(Db &$otherDb)
    {
        $this->additionalParameters = array_merge($this->additionalParameters ?? [], $otherDb->getParameters() ?? []);
    }
    public function getParameters()
    {
        return $this->additionalParameters;
    }
    /**
     * select with array column and table name
     *
     * @param  string[] $columns
     * @param  string $table
     * @return Db
     */
    private function select3($columns, $table)
    {
        $fromTable = $this->getTableNameOrQuery($table);
        $tableAlias = '';
        $columnAlias = '';
        if (is_object($table)) {
            $tableAlias = ' x';
            $columnAlias = 'x.';
            $this->copyProperties($table);
        }

        $selectedColumns = [];
        if (is_array($columns)) {
            foreach ($columns as $key => $column) {
                $quotedColumn = $this->WrapQuot($column);
                // for [ column1, column2, column3]
                if (is_numeric($key)) {
                    $selectedColumns[] = $quotedColumn;
                }
                // for [ column1 => alias1, column2 => alias2, ... ]
                else {
                    $selectedColumns[] = $this->WrapQuot($columnAlias . $key . SPACE . $quotedColumn, false);
                }
            }
        } else {
            $this->checkRaw($columns);
            $selectedColumns = [$columns];
        }
        $sql = "SELECT " . implode(',', $selectedColumns)
            . " FROM " . $fromTable . $tableAlias;
        return $this->query($sql);
    }
    /**
     * where and clause
     *
     * @param  mixed $arg1
     * @param  mixed $arg2 (optional)
     * @param  mixed $arg3 (optional)
     * @param  mixed $arg4 (optional)
     * @return Db
     */
    public function and($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        return $this->where($arg1, $arg2, $arg3, $arg4, SqlOperator::AND);
    }
    /**
     * createWhere
     *
     * @param  string $operator
     * @param  string $columnName
     * @param  string $operand
     * @param  mixed $value
     * @param  \Closure $customBind
     * @param  bool? $isRaw
     * @return Db
     */
    private function createWhere($operator, $columnName, $operand, $value, $customBind, $isRaw = false)
    {
        if (is_callable($value)) {
            $db = $this->clone();
            $db = $value($db);
            $value = $db->generateQuery();
            $isRaw = true;
        }
        $type = $this->GetType($value);

        $setWhere = new SetWhereRaw($columnName, $value, $operand, $type, $customBind, $operator);
        $columnIsRaw = $this->checkRaw($columnName);
        if ($columnIsRaw) {
            $columnName = 'p' . time();
        }
        if($this->whereValues == null){
            $this->whereValues = [];
        }
        $setWhere->BindName = str_replace('.', '_', $columnName) . '_' . $this->identity . '_' . count($this->whereValues);
        $setWhere->IsRaw = $isRaw;
        return $setWhere;
    }
    /**
     * Where
     * ```php
     * ->where('column1', 'value1')
     * // With sub where
     * ->where(fn($w) => $w->or('column1', 'value1')->or('column'))
     * // With custom operator
     * ->where('column1', '>', 'value2')
     * // With custom value example for MD5 method for value
     * ->where('column1','value2', fn($v) => "MD5($v)")
     * ```
     *
     * @param  mixed $arg1
     * @param  mixed $arg2
     * @param  mixed $arg3
     * @param  mixed $arg4
     * @param  mixed $arg5
     * @return Db
     */
    public function where($arg1, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null)
    {
        if (is_array($arg1) || is_object($arg1)) return $this->where1($arg1);

        if (!is_null($arg5)) {
            if (is_null($arg3))
                return $this->where3($arg1, $arg2, $arg3, $arg5);
            return $this->where4($arg1, $arg2, $arg3, $arg4, $arg5);
        } else if (!is_null($arg4)) {
            return $this->where4($arg1, $arg2, $arg3, $arg4, SqlOperator::AND);
        } else if (!is_null($arg3)) {
            if (is_callable($arg3)) {
                return $this->where3($arg1, $arg2, $arg3);
            }
            if (is_null($arg5))  $arg5 = SqlOperator::AND;
            return $this->where4($arg1, $arg2, $arg3, null, $arg5);
        } else if (!is_null($arg2)) {
            return $this->where2($arg1, $arg2);
        } else {
            return $this->where1($arg1);
        }
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
     * @return Db
     */
    public function or($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        if ($arg2 == null) {
            return $this->or1($arg1);
        } else if ($arg3 == null) {
            return $this->or2($arg1, $arg2);
        } else if ($arg4 == null) {
            if (is_string($arg3)) {
                // when ( column, operator, value)
                return $this->where4($arg1, $arg2, $arg3, null, SqlOperator::OR);
            } else {
                // when ( column, value, fn(x) => ('customRaw'.x) )
                return $this->where4($arg1, $arg2, $arg3, $arg4, SqlOperator::OR);
            }
        } else {
            return $this->where4($arg1, $arg2, $arg3, $arg4, SqlOperator::OR);
        }
    }
    public function whereIn($column1, $arrValues)
    {
        $in = '';
        if (is_array($arrValues)) {
            $in = implode(',', $arrValues);
        } else if (is_string($arrValues)) {
            $in = $arrValues;
        } else if (is_object($arrValues) && $arrValues instanceof Db) {
            $in = $arrValues->getQuery();
            $this->copyProperties($arrValues);
        }
        return $this->where($column1, 'IN', self::raw($in));
    }
    public function orWhereIn($column1, $arrValues)
    {
        $in = '';
        if (is_array($arrValues)) {
            $in = implode($arrValues);
        } else if (is_string($arrValues)) {
            $in = $arrValues;
        } else if (is_object($arrValues) && $arrValues instanceof Db) {
            $in = $arrValues->getQuery();
            $this->copyProperties($arrValues);
        }
        return $this->or($column1, 'IN', self::raw('(' . $in . ')'));
    }
    /**
     * Where with sub query
     *
     * @param \Db|array $wheres callback(dbUtils): Db
     * @return Db
     */
    private function where1($wheres, $operator = SqlOperator::AND)
    {
        if (is_array($wheres)) {
            $this->where1Array($wheres, $this->whereValues, $operator);
            return $this;
        } else {
            $newSubDb = $this->clone();
            $getWheres = $wheres($newSubDb);
            $indexOperator = $operator . '_' . $this->identity;
            $db = $this->parentDb ?? $this;
            $db->whereValues[$indexOperator] = $getWheres;
        }
        return $this;
    }
    /**
     * Where with sub query
     *
     * @param \Closure|array $wheres callback(dbUtils): Db
     * @return Db
     */
    private function or1($wheres)
    {
        return $this->where1($wheres, SqlOperator::OR);
    }
    /**
     * Where with array parameter
     * 
     * ```php
     * $whereCollection = [
     *    'column1' => 'value1',
     *    'column2' => 'value2',
     *    [
     *      'column4' => 'value4',
     *      'column5' => 'value5',
     *    ]
     * ]
     * ```
     *
     * @param  array|string $whereCollection
     * @param  SetWhere[] $wheretop
     * @param  null|string $operand
     * @return Db
     */
    private function where1Array($whereCollection, &$wheretop, $operator = null)
    {
        $operator_temp = $operator;
        foreach ($whereCollection as $column => $value) {
            if (is_string($column)) {
                $isRaw = $this->checkRaw($value);
                $setWhere = $this->createWhere($operator, $column, '=', $value, null, $isRaw);
                $wheretop[] = $setWhere;
            } else if (is_numeric($column)) {
                // OR / AND
                // it can be : @OR, @AND. or array( '' => '', '' => '' )
                if (
                    is_string($value)
                    && $value[0] == '@'
                ) {
                    SqlOperator::setOperand($operator);
                    $operator_temp = $operator;
                } else if (is_array($value)) {
                    $this->where1Array($value, $wheretop[], $operator);
                    $operator = $operator_temp;
                } else if (is_callable($value)) {
                    $dbClone = $this->clone();
                    $dbClone = $value($dbClone);
                    $wheretop[$operator . '_' . ($this->identity + 1)] = $dbClone;
                }
            }
        }
    }
    public function getQuery()
    {
        $tempQuery = $this->query;
        $this->generateQuery();
        $generatedQuery = $this->query;
        // back to old query
        $this->query = $tempQuery;
        return $generatedQuery;
    }
    /**
     * where with 2 parameter
     *
     * @param  string $columnName
     * @param  string $value
     * @return Db
     */
    private function where2($columnName, $value)
    {
        $this->where3($columnName, $value, null);
        return $this;
    }
    /**
     * where with 2 parameter
     *
     * @param  string $columnName
     * @param  string $value
     * @return Db
     */
    private function or2($columnName, $value)
    {
        $this->where3($columnName, $value, null, SqlOperator::OR);
        return $this;
    }
    private function where3($columnName, $value, $customBind, $operator = SqlOperator::AND)
    {
        return $this->where4($columnName, '=', $value, $customBind, $operator);
    }
    private function where4($columnName, $oOperand, $value, $customBind = null, $operator = null)
    {
        $isRaw = $this->checkRaw($value);
        $setWhere = $this->createWhere($operator, $columnName, $oOperand, $value, $customBind, $isRaw);
        // it's for generating sub query in where
        $this->whereValues[] = $setWhere;
        if ($this->parentDb)
            $this->parentDb->addParameter($setWhere);
        return $this;
    }
    /**
     * orderBy
     *
     * @param  string|array $columns
     * @param  string $orderType = Db::ASC | Db::DESC
     * @return Db
     */
    public function orderBy($columns, $orderType = Db::ASC)
    {
        if (is_array($columns)) $columns = implode(',', $columns);
        $this->orderAdditional = ' ORDER BY ' . $columns . SPACE . $orderType;
        return $this;
    }
    /**
     * Order Asc
     *
     * @param  string|array $columns
     * @return Db
     */
    public function asc($columns)
    {
        return $this->orderBy($columns, self::ASC);
    }
    /**
     * Order Desc
     *
     * @param  string|array $columns
     * @return Db
     */
    public function desc($columns)
    {
        return $this->orderBy($columns, self::DESC);
    }
    /**
     * limit
     * ```php
     * ->limit($start)
     * // or
     * ->limit($start, $length)
     * ```
     *
     * @param  int $arg1
     * @param  int $arg2 default length 1
     * @return Db
     */
    public function limit($length = 1, $start = 0)
    {
        $this->limitAdditional = ' LIMIT ' . $length . ' OFFSET ' . $start;
        return $this;
    }
    /**
     * Add JoinSet into join collections
     *
     * @param  string $tableName
     * @param  string $onColumn1
     * @param  string $onColumn2
     * @param  string $type
     * @return void
     */
    private function addJoinSet($tableName, $onColumn1, $onColumn2, $type = SqlOperator::INNER)
    {
        // check if tableName is sub query or not
        if ($tableName[0] != '(')
            $tableName = $this->WrapQuot($tableName);

        // new join set
        $joinObject = new Join();
        $joinObject->Table = $tableName;
        $joinObject->OnColumn = $this->WrapQuot($onColumn1);
        $joinObject->OnValue = $this->WrapQuot($onColumn2);
        $joinObject->JoinType = $type;

        // collect into list
        $this->joinValues[] = $joinObject;
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
     * @return Db
     */
    public function join($arg1, $arg2, $arg3, $arg4 = null, $arg5 = null)
    {
        // if $arg1 is string type
        if (is_string($arg1)) {
            return $this->join3($arg1, $arg2, $arg3, $arg4);
        } else {
            // else if $arg1 is Closure (subqueries)
            if ($arg5 == null) {
                return $this->join1($arg1, $arg2, $arg3, $arg4);
            } else {
                return $this->join2($arg1, $arg2, $arg3, $arg4, $arg5);
            }
        }
    }
    /**
     * alias for identity subqueries
     *
     * @param  string $alias
     * @return Db
     */
    public function alias($alias)
    {
        $this->identity = $alias;
        return $this;
    }
    private function newJoin(Closure $subQuery, $onColumn1, $onColumn2, $joinType = SqlOperator::INNER, $alias = null)
    {
        $dbClone = $this->clone();
        $dbClone = $subQuery($dbClone);
        if (!empty($alias))
            $dbClone->identity = $alias;
        $queryTarget = $dbClone->generateQuery();
        $this->copyProperties($dbClone);
        return $this->addJoinSet('(' . $queryTarget . ') ' . $dbClone->identity, $onColumn1, $onColumn2, $joinType);
    }
    /**
     * join
     *
     * @param  \Closure $subQuery
     * @param  string $onColumn1
     * @param  string $onColumn2
     * @param  string $type | SqlOperator::LEFT, SqlOperator::RIGHT, SqlOperator::INNER
     * @return Db
     */
    private function join1(Closure $subQuery, $onColumn1, $onColumn2, $type)
    {
        return $this->newJoin($subQuery, $onColumn1, $onColumn2, $type);
    }
    private function join2(Closure $subQuery, $alias, $onColumn1, $onColumn2, $type)
    {
        return $this->newJoin($subQuery, $onColumn1, $onColumn2, $type, $alias);
    }
    /**
     * join
     *
     * @param  string $tableName
     * @param  string $onColumn1
     * @param  string $onColumn2
     * @param  string $joinType SqlOperator::INNER | SqlOperator::LEFT | SqlOperator::RIGHT
     * @return Db
     */
    private function join3($tableName, $onColumn1, $onColumn2, $joinType = null)
    {
        return $this->addJoinSet($tableName, $onColumn1, $onColumn2, $joinType);
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
     * @return Db
     */
    public function leftJoin($arg1, $arg2, $arg3, $arg4 = null)
    {
        if (is_string($arg1))
            return $this->join($arg1, $arg2, $arg3, SqlOperator::LEFT);
        else
            return $this->join($arg1, $arg2, $arg3, $arg4, SqlOperator::LEFT);
    }
    /**
     * Right join
     * ```php
     * ->leftJoin('table2', 'table2.column1', 'table1.column1')
     * // OR
     * ->leftJoin('table2 a', 'a.column1', 'table1.column1')
     * // OR
     * ->leftJoin(fn($db) => $db->select('table3')->where(....), 
     * 'a', 'a.column1', 'table1.column1')
     * ```
     *
     * @param  Closure|string $arg1
     * @param  string $arg2
     * @param  string $arg3
     * @param  string $arg4
     * @return Db
     */
    public function rightJoin($arg1, $arg2, $arg3, $arg4 = null)
    {
        if (is_string($arg1))
            return $this->join($arg1, $arg2, $arg3, SqlOperator::RIGHT);
        else
            return $this->join($arg1, $arg2, $arg3, $arg4, SqlOperator::RIGHT);
    }
    /**
     * attachParent
     *
     * @param  Db $parent
     * @return void
     */
    public function attachParent($parent)
    {
        $this->parentDb = $parent;
    }
    /**
     * clone db object for sub queries
     *
     * @return Db
     */
    private function clone()
    {
        $cloned = new Db();
        $cloned->attachParent($this->parentDb ?? $this);
        return $cloned;
    }
    /**
     * generateWhere
     *
     * @return string
     */
    private function generateWhere()
    {
        $resultWhere = STRING_EMPTY;
        if ($this->whereValues)
            $resultWhere = $this->wrapWhere($this->whereValues);
        if (empty($resultWhere))
            return $resultWhere;
        return (!is_null($this->queryType) ? ' WHERE ' : STRING_EMPTY) . $resultWhere;
    }
    private function wrapWhere($whereValues, &$operator = null)
    {

        if($whereValues == null) return STRING_EMPTY;
        $whereLength = count($whereValues);
        if ($whereLength == 0) return STRING_EMPTY;

        $whereQuery = STRING_EMPTY;
        $firstCondition = true;
        foreach ($whereValues as $key => $_value) {
            if (is_string($key))
                $operator = substr($key, 0, strpos($key, '_'));
            if ($firstCondition) {
                $_value->Operator = STRING_EMPTY;
                $firstCondition = false;
            }
            // skip where is raw
            if ($_value instanceof SetWhereRaw && $_value->IsRaw) {
                $value = '(' . $_value->Value . ')';
                $whereQuery .= $this->whereStringMapper(
                    $_value->Operator,
                    $_value->Column,
                    $_value->ValueOperator,
                    $value
                );
                continue;
            }


            if (is_array($_value)) {
                $whereQuery .= SPACE . $operator . ' (';
                $whereQuery .= $this->wrapWhere($_value, $operator);
                $whereQuery .= ')';
            } else {
                if ($_value instanceof Db) {
                    $query = $_value->generateQuery();
                    $whereQuery .= SPACE . $operator . ' (' . $query . ')';
                } else {
                    if ($_value->IsFromChild) continue;

                    $bindingValue = $_value->Value;

                    if ($_value instanceof SetWhere)
                        $bindingValue = is_callable($_value->CustomBind) ? call_user_func($_value->CustomBind, $this->assignBindSymbol($_value->BindName)) : $this->assignBindSymbol($_value->BindName);

                    $whereQuery .= $this->whereStringMapper($_value->Operator, $this->WrapQuot($_value->Column), $_value->ValueOperator, $bindingValue);
                    // bind parameter into connection
                    $this->addParameter($_value);
                }
            }
        }
        return $whereQuery;
    }
    /**
     * whereStringMapper
     *
     * @param  mixed $operand
     * @param  mixed $column
     * @param  mixed $operator
     * @param  mixed $value
     * @return string
     */
    private function whereStringMapper($operand, $column, $operator, $value)
    {
        return SPACE . $operand . SPACE . $column . SPACE . $operator . SPACE . $value;
    }
    /**
     * groupBy
     *
     * @param  string|string[] $columns
     * @return Db
     */
    public function groupBy($columns)
    {
        if ($columns == null) return $this;

        $this->groupAdditional = ' GROUP BY ';

        if (is_string($columns))
            $this->groupAdditional .= $columns;
        else if (is_array($columns)) {
            $wrappedColumns = array_map(function ($col) {
                return $this->WrapQuot($col);
            }, $columns);
            $this->groupAdditional .= implode(',', $wrappedColumns);
        }
        return $this;
    }
    private function generateGroup()
    {
        if ($this->groupAdditional != null)
            $this->query .= $this->groupAdditional;
    }
    public function having($rawQuery)
    {
        if (!empty($rawQuery));
        $this->havingAdditional = ' HAVING ' . $rawQuery;
        return $this;
    }
    /**
     * generateJoins
     *
     * @return string
     */
    private function generateJoins()
    {
        if($this->joinValues == null) return STRING_EMPTY;
        $joinLength = count($this->joinValues);
        if ($joinLength == 0) return STRING_EMPTY;

        $joinQuery = STRING_EMPTY;
        foreach ($this->joinValues as $_value) {
            $joinType = $_value->JoinType;
            $joinQuery .= SPACE . $joinType .
                ' JOIN ' . $_value->Table . ' ON ' . $_value->OnColumn . '=' . $_value->OnValue;
        }
        return $joinQuery;
    }

    /**
     * generateQuery
     *
     * @return string
     */
    public function generateQuery()
    {
        $this->checkDistinctQuery();
        $this->addOptionJoin();
        $this->addOptionWhere();
        $this->addOptionGroup();
        $this->addOptionOrder();
        $this->addOptionLimit();
        if ($this->parentDb != null)
            $this->parentDb = null;
        return $this->query;
    }

    protected function clear()
    {
        $this->whereValues = [];
        $this->orderAdditional = STRING_EMPTY;
        $this->limitAdditional = STRING_EMPTY;
        $this->joinValues = [];
        $this->parentDb = null;
        $this->queryType = null;
        $this->additionalParameters = [];
        $this->query = STRING_EMPTY;
    }

    private function addOptionOrder()
    {
        $this->query .= $this->orderAdditional;
    }
    private function addOptionLimit()
    {
        $this->query .= $this->limitAdditional;
    }
    private function addOptionWhere()
    {
        $this->query .= $this->generateWhere();
    }
    private function addOptionGroup()
    {
        $this->query .= $this->generateGroup();
    }

    private function addOptionJoin()
    {
        $this->query .= $this->generateJoins();
    }
    /**
     * check data is exist
     *
     * @return bool
     */
    public function exist()
    {
        $result = $this->limit(1)->read();
        return $result != null;
    }
    protected function attachParameter()
    {
        $this->bindParameters($this->additionalParameters);
    }
    private function bindParameters(&$parameters)
    {
        foreach ($parameters as $parameter) {
            if (is_array($parameter))
                $this->bindParameters($parameter);
            else {
                $parameter->BindName = $this->assignBindSymbol($parameter->BindName);
                $this->statement->bindParam($parameter->BindName, $parameter->Value, $parameter->VType);
            }
        }
    }

    /**
     * insert
     *
     * @param  string $tableName
     * @param  array $data
     * @return SqlModel
     */
    public function insert($tableName, $data = null)
    {
        $db = $this->sqlModel->insert($tableName);
        if ($data == null)
            return $db;
        $this->attachDbValues($db, $data);
        return $db;
    }
    public function bulkInsertObject($tableName, $arrayData)
    {
        $columns = array_keys($arrayData[0]);
        return $this->bulkInsertArray($tableName, $columns, $arrayData);
    }
    public function bulkInsertArray($tableName, $columns, $arrayData)
    {
        $db = $this->sqlModel->insert($tableName);
        return $db->bulkInsert($columns, $arrayData);
    }
    private function attachDbValues(SqlModel &$dbUtil, &$data)
    {
        if ($data == null) return;

        foreach ($data as $columnName => $value) {
            $dbUtil->addValue($columnName, $value);
        }
    }
    /**
     * update
     *
     * @param  string $tableName
     * @return SqlModel
     */
    public function update($tableName, $data = null)
    {
        $db = $this->sqlModel->update($tableName);
        if ($data == null)
            return $db;
        $this->attachDbValues($db, $data);
        return $db;
    }
    public function delete($tableName)
    {
        return $this->sqlModel->delete($tableName);
    }
    public function execute()
    {
        try {
            $this->generateQuery();
            $this->statement = $this->connection->prepare($this->query);
            $this->attachParameter();
            $result = $this->statement->execute();
            $this->clear();
            return $result;
        } catch (Exception $ex) {
            $de = new dsException($ex);
            $de->addMessage('<br>Query : <b>' . $this->query . '</b>');
            $de->addMessage('<br>Parameters : <pre><code>' . print_r($this->additionalParameters, true) . '</code></pre>');
            $de->show_exception(true);
            die();
        }
    }

    public function readAll($fetch_type = PDO::FETCH_OBJ)
    {
        $this->execute();
        $result = $this->statement->fetchAll($fetch_type);
        return $result;
    }
    public function read($fetch_type = PDO::FETCH_OBJ)
    {
        $this->execute();
        $result = $this->statement->fetch($fetch_type);
        return $result;
    }
    public static function raw($value)
    {
        return '!!' . $value;
    }
    /**
     * get_all
     *
     * @param  int $target PDO::FETCH_TYPE
     * @return array
     */
    public function get_all($target = PDO::FETCH_OBJ)
    {
        return $this->readAll($target);
    }
    public function count_rows()
    {
        return count($this->get_all(PDO::FETCH_ASSOC));
    }
    public function get()
    {
        return $this->get_object();
    }
    public function first()
    {
        return $this->limit()->get_row_object();
    }
    public function get_array()
    {
        return $this->get_all(PDO::FETCH_NUM);
    }
    public function get_object()
    {
        return $this->get_all(PDO::FETCH_OBJ);
    }
    public function get_assoc()
    {
        return $this->get_all(PDO::FETCH_ASSOC);
    }
    public function get_bound()
    {
        return $this->get_all(PDO::FETCH_BOUND);
    }
    public function get_into()
    {
        return $this->get_all(PDO::FETCH_INTO);
    }
    public function get_lazy()
    {
        return $this->get_all(PDO::FETCH_LAZY);
    }
    public function get_named()
    {
        return $this->get_all(PDO::FETCH_NAMED);
    }
    public function get_single_array()
    {
        return $this->get_all(PDO::FETCH_COLUMN);
    }
    public function get_row_array()
    {
        return $this->get_row(PDO::FETCH_NUM);
    }
    public function get_row_object()
    {
        return $this->get_row(PDO::FETCH_OBJ);
    }
    public function get_row_assoc()
    {
        return $this->get_row(PDO::FETCH_ASSOC);
    }
    public function get_row_bound()
    {
        return $this->get_row(PDO::FETCH_BOUND);
    }
    public function get_row_into()
    {
        return $this->get_row(PDO::FETCH_INTO);
    }
    public function get_row_lazy()
    {
        return $this->get_row(PDO::FETCH_LAZY);
    }
    public function get_row_named()
    {
        return $this->get_row(PDO::FETCH_NAMED);
    }
    public function get_row($target = NULL)
    {
        $target = is_null($target) ? PDO::FETCH_BOTH : $target;
        return $this->read($target);
    }
    public function get_exist()
    {
        return $this->exist();
    }
}
