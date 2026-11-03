<?php

declare(strict_types=1);

namespace Ds\Database;

final class SchemaBuilder
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint();
        $callback($blueprint);
        $sql = 'CREATE TABLE ' . $this->database->quoteIdentifier($table)
            . ' (' . $blueprint->compile($this->database) . ')';
        $this->database->execute($sql);
    }

    public function drop(string $table): void
    {
        $this->database->execute('DROP TABLE ' . $this->database->quoteIdentifier($table));
    }

    public function dropIfExists(string $table): void
    {
        $this->database->execute('DROP TABLE IF EXISTS ' . $this->database->quoteIdentifier($table));
    }

    public function hasTable(string $table): bool
    {
        $driver = $this->database->driver();
        $exists = match ($driver) {
            'sqlite' => $this->database->select(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :name",
                ['name' => $table]
            ) !== [],
            'mysql' => $this->database->select(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :name',
                ['name' => $table]
            ) !== [],
            'pgsql' => $this->database->select(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :name',
                ['name' => $table]
            ) !== [],
            'sqlsrv' => $this->database->select(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = :name',
                ['name' => $table]
            ) !== [],
            default => throw new \InvalidArgumentException("Unsupported database driver [{$driver}]."),
        };
        return $exists;
    }
}
