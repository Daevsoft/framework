<?php

namespace Ds\Foundations\Migrate;

use Ds\Foundations\Config\Env;
use Ds\Foundations\Connection\QueryCommon;

class Column extends SqlTexter
{
    private $alterQuery = null;
    use QueryCommon;
    public function __construct()
    {
        $this->setupProvider();
    }

    private function separator()
    {
        if ($this->current != '') {
            $this->_add(', ');
        }
    }
    public function modify()
    {
        $driver = Env::get('DB_DRIVER');
        if ($driver == MYSQL) {
            $this->alterQuery = ' MODIFY COLUMN';
        }else {
            $this->alterQuery = ' ALTER COLUMN';
        }
        return $this;
    }
    public function add()
    {
        // $this->separator();
        $this->alterQuery = ' ADD COLUMN';
        return $this;
    }
    public function drop($column)
    {
        $this->separator();
        $this->_add(' DROP COLUMN IF EXISTS ' . $this->WrapQuot($column));
        $this->alterQuery = STRING_EMPTY;
        return $this;
    }
    public function change($column)
    {
        $this->alterQuery = (' CHANGE ' . $this->WrapQuot($column));
        return $this;
    }

    private function newColumn(String $column, $type)
    {
        $this->separator();
        $text = $this->alterQuery . ' ' . $this->WrapQuot($column) . ' ' . $type;
        $this->_add($text);
        return $this;
    }
    public function int($columnName, $length = 255)
    {
        $integer = 'INT(' . $length . ')';
        if (Env::get('DB_DRIVER') == SQLITE) {
            $integer = 'INTEGER';
        }
        return $this->newColumn($columnName, $integer);
    }
    public function boolean($columnName)
    {
        return $this->newColumn($columnName, 'BOOLEAN');
    }
    public function string($columnName, $length = 255)
    {
        return $this->newColumn($columnName, 'VARCHAR(' . $length . ')');
    }
    public function enum($columnName, $values)
    {
        $col = array_map(fn($item) => ('\''.$item.'\''), $values);
        return $this->newColumn($columnName, 'ENUM(' . implode(',', $col) . ')');
    }
    public function char($columnName)
    {
        return $this->newColumn($columnName, 'CHAR(1)');
    }
    public function text($columnName)
    {
        return $this->newColumn($columnName, 'TEXT');
    }
    public function date($columnName)
    {
        return $this->newColumn($columnName, 'DATE');
    }
    public function datetime($columnName)
    {
        return $this->newColumn($columnName, 'DATETIME');
    }
    public function timestamp($columnName, $allowNull = false)
    {
        $null = $allowNull ? '' : ' NOT NULL DEFAULT CURRENT_TIMESTAMP';
        return $this->newColumn($columnName, 'TIMESTAMP' . $null);
    }
    // -------------------- ATTRIBUTE
    public function primaryKey()
    {
        $this->_add(' PRIMARY KEY');
        return $this;
    }
    public function id($name = 'id')
    {
        $this->int($name)->primaryKey()->autoincrement();
        // if( Env::get('DB_DRIVER') != SQLITE) {
        //     $this->notNull();
        // }
        return $this;
    }
    public function notNull($default = null)
    {
        $this->_add(' NOT NULL');
        if ($default != null) {
            if(is_bool($default)) {
                $default = $default ? 'TRUE' : 'FALSE';
            }
            $this->_add(' DEFAULT ' . $default);
        }
        return $this;
    }
    public function updatedAt()
    {
        $this->timestamp('updated_at');
        return $this;
    }
    public function createdAt()
    {
        $this->timestamp('created_at');
        return $this;
    }
    public function autoincrement()
    {

        if (Env::get('DB_DRIVER') == SQLITE) {
            $this->_add(' AUTOINCREMENT');
        } else {
            $this->_add(' AUTO_INCREMENT');
        }
        return $this;
    }
    // -------------------- END ATTRIBUTE
}
