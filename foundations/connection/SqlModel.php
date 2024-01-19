<?php

namespace Ds\Foundations\Connection;

use Ds\Foundations\Connection\Arch\QueryCommon;
use Ds\Foundations\Connection\Arch\Sets\Set;
use Ds\Foundations\Connection\Arch\Sets\SetRaw;
use Ds\Foundations\Connection\Arch\Sets\SetWhere;
use Ds\Foundations\Connection\Arch\Sets\SetWhereRaw;
use Ds\helper\Str;

class SqlModel extends QueryCommon
{

    /**
     * query
     *
     * @var string
     */
    private $query;
    /**
     * sqlModelType SqlModel::INSERT, SqlModel::UPDATE, SqlModel::DELETE
     *
     * @var string
     */
    private $sqlModelType;
    /**
     * columnValues
     *
     * @var array<Set>
     */
    private $columnValues;
    /**
     * dbUtils
     *
     * @var Db
     */
    private $dbUtils;
    /**
     * whereValues
     *
     * @var array<SetWhere>
     */
    private $whereValues;
    /**
     * __construct
     *
     * @param  Db $dbUtils
     * @param  string $quotSql
     * @return void
     */
    public function __construct($dbUtils, $quotSql, $endQuotSql, $bindSymbol)
    {
        $this->dbUtils = $dbUtils;
        $this->quotSql = $quotSql;
        $this->endQuotSql = $endQuotSql;
        $this->bindSymbol = $bindSymbol;
        $this->columnValues = array();
        $this->whereValues = array();
    }
    /**
     * insert
     *
     * @param  string $tableName
     * @return SqlModel
     */
    public function insert($tableName)
    {
        $this->sqlModelType = self::INSERT;
        $this->query = $this->sqlModelType . " INTO " . $this->WrapQuot($tableName);
        return $this;
    }
    /**
     * update
     *
     * @param  string $tableName
     * @return SqlModel
     */
    public function update($tableName)
    {
        $this->sqlModelType = self::UPDATE;
        $this->query = $this->sqlModelType . " " . $this->WrapQuot($tableName);
        return $this;
    }
    /**
     * delete
     *
     * @param  string $tableName
     * @return SqlModel
     */
    public function delete($tableName): SqlModel
    {
        $this->sqlModelType = self::DELETE;
        $this->query = $this->sqlModelType . " FROM " . $this->WrapQuot($tableName);
        return $this;
    }
    /**
     * where Where condition with default AND
     * 
     * @param string $columnName
     * @param mixed $value
     * @param string $oOperator value operator
     * @param string $operator chain condition operator
     * @param \Closure $customBind custom raw sql when binding
     * @return SqlModel
     */
    public function where($columnName, $value, $oOperator = '=', $customBind = null, $operator = 'AND')
    {
        $setWhere = new SetWhere($columnName, $value, $oOperator, $this->GetType($value), $customBind, $operator);
        $this->whereValues[] = $setWhere;
        return $this;
    }
    /**
     * orWhere Where condition with OR
     *
     * @param string $columnName
     * @param mixed $value
     * @param string $oOperator value operator
     * @param string $operator chain condition operator
     * @param \Closure $customBind custom raw sql when binding
     * @return SqlModel
     */
    public function orWhere($columnName, $value, $oOperator = '=', $customBind = null)
    {
        return $this->where($columnName, $value, $oOperator, $customBind, 'OR');
    }
    /**
     * adding value into prepare query
     *
     * @param string $columnName
     * @param mixed $value
     * @param \Closure $customBind
     * @return SqlModel
     */
    public function addValue($columnName, $value, $customBind = null)
    {
        $bindName = Str::replace($columnName, '.', '_');
        $set = new Set($columnName, $value, $this->GetType($value), $bindName, $customBind);
        $this->columnValues[] = $set;
        return $this;
    }
    /**
     * addRawValue
     *
     * @param  string $columnName
     * @param  mixed $value
     * @return SqlModel
     */
    public function addRawValue($columnName, $value)
    {
        $this->columnValues[] = new SetRaw($columnName, $value);
        return $this;
    }
    /**
     * setValue
     *
     * @param  string $columnName
     * @param  mixed $value
     * @param  \Closure $customBind
     * @return SqlModel
     */
    public function setValue($columnName,  $value,  $customBind = null)
    {
        $this->addValue($columnName, $value, $this->GetType($value), $customBind);
        return $this;
    }
    /**
     * setRawValue
     *
     * @param  string $columnName
     * @param  string $value
     * @return SqlModel
     */
    public function setRawValue($columnName,  $value)
    {
        $this->addRawValue($columnName, $value);
        return $this;
    }

    /**
     * execute
     *
     * @param  Action<mixed> $callback
     * @return bool|int
     */
    public function execute()
    {
        switch ($this->sqlModelType) {
            case self::INSERT:
                $this->setupInsertQuery();
                break;
            case self::UPDATE:
                $this->setupUpdateQuery();
                break;
            case self::DELETE:
                $this->setupDeleteQuery();
                break;
            case self::BULK_INSERT:;
                break;
            default:
                return null;
                break;
        }

        $this->dbUtils->query($this->query);

        foreach ($this->columnValues as $_value) {
            if ($_value instanceof SetRaw && $_value->IsRaw)
                continue;

            $this->dbUtils->addParameter($_value);
        }
        foreach ($this->whereValues as $_value) {
            $this->dbUtils->addParameter($_value);
        }
        $this->clear();
        return $this->dbUtils->execute();
    }

    /**
     * clear binding collections
     *
     * @return void
     */
    private function clear()
    {
        $this->whereValues = array();
        $this->columnValues = array();
    }

    /**
     * setup for sql query update
     *
     * @return void
     */
    private function setupUpdateQuery()
    {
        $this->query .= " SET ";
        $setQuery = STRING_EMPTY;

        foreach ($this->columnValues as $_value) {
            $bindingValue = STRING_EMPTY;
            if ($_value instanceof SetRaw && $_value->IsRaw) {
                $bindingValue = $_value->Value;
            } else {
                $bindingValue = $_value->CustomBind != null ?
                    ($_value->CustomBind)($this->bindSymbol . $_value->BindName) :
                    $this->bindSymbol . $_value->BindName;
            }
            $setQuery .= ',' . $this->WrapQuot($_value->Column) . '=' . $bindingValue;
        }
        $this->query .= trim($setQuery, ',');

        $this->generateWhere();
    }
    /**
     * setup for sql query insert
     *
     * @return void
     */
    private function setupInsertQuery()
    {
        $columns = STRING_EMPTY;
        $bindings = STRING_EMPTY;
        foreach ($this->columnValues as $_value) {
            $columns .= ',' . $this->WrapQuot($_value->Column);
            if (($_value instanceof SetRaw) && $_value->IsRaw) {
                $bindings .= "," . $_value->Value;
            } else {
                $bindings .= "," . ($_value->CustomBind != null ?
                    ($_value->CustomBind)($this->bindSymbol . $_value->BindName) :
                    $this->bindSymbol . $_value->BindName);
            }
        }
        $this->query .= "(" . trim($columns, ',') . ") VALUES(" . trim($bindings, ',') . ")";
    }
    /**
     * setup for sql query delete
     *
     * @return void
     */
    private function setupDeleteQuery()
    {
        $this->generateWhere();
    }
    /**
     * generate where conditions query
     *
     * @return void
     */
    private function generateWhere()
    {
        $whereQuery = STRING_EMPTY;
        $firstCondition = true;
        foreach ($this->whereValues as $_value) {
            if ($firstCondition) {
                $_value->Operator = STRING_EMPTY;
                $firstCondition = false;
            }
            $bindingValue = STRING_EMPTY;
            if (($_value instanceof SetWhereRaw) && $_value->IsRaw) {
                $bindingValue = $_value->Value;
            } else {
                $bindingValue = $_value->CustomBind != null ?
                    ($_value->CustomBind)($this->bindSymbol . $_value->BindName) :
                    $this->bindSymbol . $_value->BindName;
            }
            $whereQuery .= ' ' . $_value->Operator . " " . $this->WrapQuot($_value->Column) . " " . $_value->ValueOperator . " " . $bindingValue;
        }
        $this->query .= " WHERE" . $whereQuery;
    }
    public function bulkInsert($columns, $arrayData)
    {
        $this->sqlModelType = self::BULK_INSERT;

        $columnsQuery = implode(', ', array_map(function ($column) {
            return $this->WrapQuot($column);
        }, $columns));
        $this->query .= '(' . $columnsQuery . ') VALUES ';

        $this->query .= implode(',', array_map(function ($row) {
            $isContainKey = array_keys($row)[0] !== 0; // check is not object
            $values = null;
            if ($isContainKey) {
                $values = array_values($row);
            } else {
                $values = $row;
            }
            return '(' . implode(',', array_map(function ($value) {
                return '\'' . $value . '\'';
            }, $values)) . ')';
        }, $arrayData));

        return $this;
    }
}
