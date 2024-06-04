<?php
namespace Ds\Foundations\Commands\Migrate;

class Column extends SqlTexter
{
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
    return $this->add(' ' . trim($columnName) . ' INT(' . $length . ')');
  }
  public function string($columnName, $length = 255)
  {
    return $this->add(' ' . trim($columnName) . ' VARCHAR(' . $length . ')');
  }
  public function text($columnName)
  {
    return $this->add(' ' . trim($columnName) . ' TEXT');
  }
  public function timestamp($columnName)
  {
    return $this->add(' ' . trim($columnName) . ' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
  }
  // -------------------- ATTRIBUTE
  public function primaryKey()
  {
    $this->_add(' PRIMARY KEY NOT NULL');
    return $this;
  }
  public function notNull()
  {
    $this->_add(' NOT NULL');
    return $this;
  }
  public function autoincrement()
  {
    $this->_add(' AUTOINCREMENT');
    return $this;
  }
  // -------------------- END ATTRIBUTE
}