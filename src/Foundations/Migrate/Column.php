<?php

namespace Ds\Foundations\Migrate;

use Ds\Foundations\Config\Env;
use Ds\Foundations\Connection\QueryCommon;

class Column extends SqlTexter
{
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
    private function add(String $text)
    {
        $this->separator();
        $this->_add($text);
        return $this;
    }
    public function int($columnName, $length = 255)
    {
        $integer = 'INT(' . $length . ')';
        if (Env::get('DB_DRIVER') == SQLITE) {
            $integer = 'INTEGER';
        }
        return $this->add(' ' . $this->WrapQuot($columnName) . ' ' . $integer);
    }
    public function string($columnName, $length = 255)
    {
        return $this->add(' ' . $this->WrapQuot($columnName) . ' VARCHAR(' . $length . ')');
    }
    public function char($columnName)
    {
        return $this->add(' ' . $this->WrapQuot($columnName) . ' CHAR(1)');
    }
    public function text($columnName)
    {
        return $this->add(' ' . $this->WrapQuot($columnName) . ' TEXT');
    }
    public function date($columnName)
    {
        return $this->add(' ' . $this->WrapQuot($columnName) . ' DATE');
    }
    public function datetime($columnName)
    {
        return $this->add(' ' . $this->WrapQuot($columnName) . ' DATETIME');
    }
    public function timestamp($columnName)
    {
        return $this->add(' ' . $this->WrapQuot($columnName) . ' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
    // -------------------- ATTRIBUTE
    public function primaryKey()
    {
        $this->_add(' PRIMARY KEY');
        return $this;
    }
    public function id($name = 'id')
    {
        $this->int($name)->notNull()->autoincrement()->primaryKey();
        return $this;
    }
    public function notNull($default = null)
    {
        $this->_add(' NOT NULL');
        if ($default != null) {
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
