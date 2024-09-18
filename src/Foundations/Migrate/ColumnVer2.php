<?php

namespace Ds\Foundations\Migrate;

use Ds\Foundations\Config\Env;

class ColumnPipe
{
    public String $name;
    public String $type;
}

class Column extends SqlTexter
{
    /**
     * @var ColumnPipe[] $columnPipes
     */
    public $columnPipes = [];

    private function separator()
    {
        if ($this->current != '') {
            $this->_add(', ');
        }
    }
    private function addPipe(ColumnPipe $pipe)
    {
        $this->columnPipes[] = $pipe;
    }
    private function add(String $column, $type)
    {
        // $this->separator();
        // $this->_add($text);
        $pipe = new ColumnPipe(trim($column), $type);
        $this->addPipe($pipe);
        return $this;
    }
    public function int($columnName, $length = 255)
    {
        $integer = 'INT(' . $length . ')';
        if (Env::get('DB_DRIVER') == SQLITE) {
            $integer = 'INTEGER';
        }
        return $this->add($columnName, $integer);
    }
    public function string($columnName, $length = 255)
    {
        return $this->add($columnName, 'VARCHAR(' . $length . ')');
    }
    public function char($columnName)
    {
        return $this->add($columnName, 'CHAR(1)');
    }
    public function text($columnName)
    {
        return $this->add($columnName, 'TEXT');
    }
    public function date($columnName)
    {
        return $this->add($columnName, 'DATE');
    }
    public function datetime($columnName)
    {
        return $this->add($columnName, 'DATETIME');
    }
    public function timestamp($columnName, $allowNull = false)
    {
        $null = $allowNull ? '' : ' NOT NULL DEFAULT CURRENT_TIMESTAMP';
        return $this->add($columnName, 'TIMESTAMP' . $null);
    }
    // -------------------- ATTRIBUTE
    private function addAttribute($text)
    {
        $last = count($this->columnPipes) - 1;
        $this->columnPipes[$last]->type = $text;
    }
    public function primaryKey()
    {
        $this->addAttribute(' PRIMARY KEY');
        return $this;
    }
    public function id($name = 'id')
    {
        $this->int($name)->notNull()->autoincrement()->primaryKey();
        return $this;
    }
    public function notNull($default = null)
    {
        $this->addAttribute(' NOT NULL');
        if ($default != null) {
            $this->addAttribute(' DEFAULT ' . $default);
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
