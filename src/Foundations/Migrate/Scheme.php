<?php

namespace Ds\Foundations\Migrate;

use Closure;
use Ds\Foundations\Commands\Console;
use Ds\Foundations\Connection\DatabaseProvider;
use Exception;
use IteratorIterator;

class Scheme extends SqlTexter
{
    public bool $recordMigration = true;
    private MigrationModel $migration;
    public $migrationFilename;
    private $connection;
    public function __construct()
    {
        mock(DatabaseProvider::class);
        $this->connection = DatabaseProvider::getConnection();
        $this->migration = new MigrationModel();
        $this->prepareMigrations();
    }
    private function prepareMigrations()
    {
        if (!$this->migration->tableExist()) {
            $migration = require_once __DIR__ . SLASH . 'database' . SLASH . '0000_00_00_create_table_migrations.php';
            $this->recordMigration = false;
            $migration->up($this);
            $this->recordMigration = true;
        }
    }
    public function wrap($value)
    {
        return $this->connection->WrapQuot($value);
    }

    public function alter($table, Closure $columnCallback)
    {
        $this->_add('ALTER TABLE ' . $this->wrap($table) . ' ');
        $column = new Column();
        $columnCallback($column);
        $this->_add($column->getRaw());
        $this->_add(';');
        $this->commit();
        return $this;
    }

    public function createTable(String $name, Closure $columnCallback): Scheme
    {
        $this->_add('CREATE TABLE ' . $this->wrap($name) . ' (');
        $column = new Column();
        $columnCallback($column);
        $this->_add($column->getRaw());
        $this->_add(');');
        $this->commit();
        return $this;
    }
    public function dropTable(String $name): Scheme
    {
        $this->_add('DROP TABLE ' . $this->wrap($name));
        $this->commit();
        return $this;
    }
    public function dropIfExist(string $table): Scheme
    {
        $this->_add('DROP TABLE IF EXISTS ' . $this->wrap($table) . ';');
        $this->commit();
        return $this;
    }
    public function commit()
    {
        try {
            $queryRaw = $this->getRaw();
            if ($this->recordMigration) {
                $data = [
                    'raw' => $queryRaw,
                    'filename' => $this->migrationFilename,
                ];
                $wasExecuted = $this->isWasExecuted($data);
                if (!$wasExecuted) {
                    $this->connection->query($queryRaw)->execute(true);
                    $this->saveMigration($data);
                }
            } else {
                $this->connection->query($queryRaw)->execute(true);
            }
        } catch (Exception $th) {
            Console::writeln(' Migration was skipped! [ERROR] ' . $th->getMessage(), Console::DARK_GRAY);
        }
    }
    private function saveMigration(array $data)
    {
        $data['batch'] = $this->migration->getBatch($this->migrationFilename);
        MigrationModel::save($data);
    }
    private function isWasExecuted($data): bool
    {
        $migrations = MigrationModel::findsWhere($data);
        return count($migrations) > 0;
    }
}

class Collection extends IteratorIterator {}
