<?php
namespace Ds\Foundations\Commands\Migrate;

use Closure;

class Scheme extends SqlTexter
{
  public function create(String $name, Closure $columnCallback)
  {
    $this->_add('CREATE TABLE ' . $name . ' (');
    $column = new Column();
    $columnCallback($column);
    $this->_add($column->getRaw());
    $this->_add(');');
    return $this;
  }
  public function dropTable(String $name)
  {
    $this->_add('DROP TABLE ' . $name);
  }
  public function commit()
  {
    echo $this->getRaw();
  }
}