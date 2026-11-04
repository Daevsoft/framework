<?php

declare(strict_types=1);

namespace Ds\Database;

final class Blueprint
{
    private array $columns = [];

    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->add(new ColumnDefinition($name, 'id'))->autoIncrement();
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        if ($length < 1 || $length > 65535) {
            throw new \InvalidArgumentException('String column length must be between 1 and 65535.');
        }
        return $this->add(new ColumnDefinition($name, 'string', $length));
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->add(new ColumnDefinition($name, 'integer'));
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->add(new ColumnDefinition($name, 'boolean'));
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->add(new ColumnDefinition($name, 'text'));
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->add(new ColumnDefinition($name, 'date'));
    }

    public function datetime(string $name): ColumnDefinition
    {
        return $this->add(new ColumnDefinition($name, 'datetime'));
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->add(new ColumnDefinition($name, 'timestamp'));
    }

    public function compile(DatabaseManager $database): string
    {
        if ($this->columns === []) {
            throw new \LogicException('A table must contain at least one column.');
        }
        return implode(', ', array_map(
            static fn (ColumnDefinition $column): string => $column->compile($database),
            $this->columns
        ));
    }

    /** Compile exactly one column for ALTER TABLE ADD COLUMN. */
    public function compileColumn(DatabaseManager $database): string
    {
        if (count($this->columns) !== 1) {
            throw new \LogicException('An ALTER TABLE operation must define exactly one column.');
        }

        return $this->columns[0]->compile($database);
    }

    private function add(ColumnDefinition $column): ColumnDefinition
    {
        $this->columns[] = $column;
        return $column;
    }
}
