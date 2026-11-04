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

    /**
     * Add one column using the same validated column definitions as CREATE TABLE.
     * DDL is intentionally issued directly because transaction support varies by driver.
     */
    public function addColumn(string $table, callable $callback): void
    {
        $blueprint = new Blueprint();
        $callback($blueprint);
        $column = $blueprint->compileColumn($this->database);

        $this->database->execute(
            'ALTER TABLE ' . $this->database->quoteIdentifier($table) . ' ADD COLUMN ' . $column
        );
    }

    /** Drop a column on database versions that support ALTER TABLE DROP COLUMN. */
    public function dropColumn(string $table, string $column): void
    {
        if ($this->database->driver() === 'sqlite') {
            $version = $this->database->select('SELECT sqlite_version() AS version')[0]['version'] ?? '0.0.0';
            if (version_compare((string) $version, '3.35.0', '<')) {
                throw new \\RuntimeException(
                    'SQLite DROP COLUMN requires SQLite 3.35.0 or newer; rebuild the table for older versions.'
                );
            }
        }

        $this->database->execute(
            'ALTER TABLE ' . $this->database->quoteIdentifier($table)
                . ' DROP COLUMN ' . $this->database->quoteIdentifier($column)
        );
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
