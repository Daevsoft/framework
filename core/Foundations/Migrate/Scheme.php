<?php

declare(strict_types=1);

namespace Ds\Foundations\Migrate;

use Ds\Database\Blueprint;
use Ds\Database\ColumnDefinition;
use Ds\Database\DatabaseManager;
use PDO;
use LogicException;

final class Scheme
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly PDO $connection
    ) {
    }

    public function createTable(string $name, callable $callback): self
    {
        $blueprint = new Blueprint();
        $callback(new Column($blueprint));
        $this->connection->exec(
            'CREATE TABLE ' . $this->database->quoteIdentifier($name)
            . ' (' . $blueprint->compile($this->database) . ')'
        );
        return $this;
    }

    public function dropTable(string $name): self
    {
        $this->connection->exec('DROP TABLE ' . $this->database->quoteIdentifier($name));
        return $this;
    }

    public function dropIfExist(string $name): self
    {
        $this->connection->exec('DROP TABLE IF EXISTS ' . $this->database->quoteIdentifier($name));
        return $this;
    }

    public function alter(string $table, callable $callback): never
    {
        throw new LogicException(
            'Legacy Scheme::alter is not available in the compatibility bridge; use a driver-specific v4 migration.'
        );
    }
}

final class Column
{
    public function __construct(private readonly Blueprint $blueprint)
    {
    }

    public function id(string $name = 'id'): ColumnDefinition { return $this->blueprint->id($name); }
    public function int(string $name, int $length = 255): ColumnDefinition { return $this->blueprint->integer($name); }
    public function integer(string $name): ColumnDefinition { return $this->blueprint->integer($name); }
    public function boolean(string $name): ColumnDefinition { return $this->blueprint->boolean($name); }
    public function string(string $name, int $length = 255): ColumnDefinition { return $this->blueprint->string($name, $length); }
    public function text(string $name): ColumnDefinition { return $this->blueprint->text($name); }
    public function date(string $name): ColumnDefinition { return $this->blueprint->date($name); }
    public function datetime(string $name): ColumnDefinition { return $this->blueprint->datetime($name); }
    public function timestamp(string $name, bool $allowNull = false): ColumnDefinition
    {
        $column = $this->blueprint->timestamp($name);
        return $allowNull ? $column->nullable() : $column->notNull();
    }
    public function createdAt(): ColumnDefinition { return $this->timestamp('created_at'); }
    public function updatedAt(): ColumnDefinition { return $this->timestamp('updated_at'); }
}
