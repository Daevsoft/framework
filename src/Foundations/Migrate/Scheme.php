<?php

namespace Ds\Foundations\Migrate;

use Closure;
use Ds\Foundations\Commands\Console;
use Ds\Foundations\Connection\DatabaseProvider;

class Scheme extends SqlTexter
{
    private $connection;
    public function __construct()
    {
        mock(DatabaseProvider::class);
        $this->connection = DatabaseProvider::getConnection();
    }
    public function wrap($value)
    {
        return $this->connection->WrapQuot($value);
    }

    public function alter($table, Closure $columnCallback)
    {}

    public function createTable(String $name, Closure $columnCallback)
    {
        $this->_add('CREATE TABLE ' . $this->wrap($name) . ' (');
        $column = new Column();
        $columnCallback($column);
        $this->_add($column->getRaw());
        $this->_add(');');
        $this->commit();
    }
    public function dropTable(String $name)
    {
        $this->_add('DROP TABLE ' . $this->wrap($name));
        $this->commit();
    }
    public function dropIfExist(string $table)
    {
        $this->_add('DROP TABLE IF EXISTS ' . $this->wrap($table) . ';');
        $this->commit();
    }
    public function commit()
    {
        try {
            $queryRaw = $this->getRaw();
            $this->connection->query($queryRaw)->execute(true);
        } catch (\Throwable $th) {
            Console::writeln(' Migration was skipped! [ERROR] ' . $th->getMessage(), Console::DARK_GRAY);
        }
    }
}
