<?php

declare(strict_types=1);

namespace Ds\Database;

final class ColumnDefinition
{
    private bool $isNullable = false;
    private bool $isUnique = false;
    private bool $isPrimary = false;
    private bool $isAutoIncrement = false;
    private bool $hasDefault = false;
    private mixed $defaultValue = null;

    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly ?int $length = null
    ) {
        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $name) !== 1) {
            throw new \InvalidArgumentException("Invalid column name [{$name}].");
        }
    }

    public function nullable(bool $nullable = true): self
    {
        $this->isNullable = $nullable;
        return $this;
    }

    public function notNull(): self
    {
        return $this->nullable(false);
    }

    public function unique(): self
    {
        $this->isUnique = true;
        return $this;
    }

    public function primary(): self
    {
        $this->isPrimary = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->isAutoIncrement = true;
        return $this;
    }

    public function default(mixed $value): self
    {
        if (!is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException('Column defaults must be scalar or null.');
        }
        $this->hasDefault = true;
        $this->defaultValue = $value;
        return $this;
    }

    public function compile(DatabaseManager $database): string
    {
        $driver = $database->driver();
        $name = $database->quoteIdentifier($this->name);
        $type = match ($this->type) {
            'string' => 'VARCHAR(' . ($this->length ?? 255) . ')',
            'integer' => $driver === 'sqlite' ? 'INTEGER' : 'INT',
            'boolean' => 'BOOLEAN',
            'text' => 'TEXT',
            'date' => 'DATE',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'id' => match ($driver) {
                'sqlite' => 'INTEGER',
                'pgsql' => 'BIGSERIAL',
                default => 'BIGINT',
            },
            default => throw new \LogicException("Unsupported column type [{$this->type}]."),
        };

        $sql = $name . ' ' . $type;
        if ($this->isAutoIncrement) {
            $sql .= match ($driver) {
                'sqlite' => ' PRIMARY KEY AUTOINCREMENT',
                'pgsql' => ' PRIMARY KEY',
                'sqlsrv' => ' IDENTITY(1,1) PRIMARY KEY',
                default => ' AUTO_INCREMENT PRIMARY KEY',
            };
        } elseif ($this->isPrimary) {
            $sql .= ' PRIMARY KEY';
        }

        if (!$this->isNullable && !$this->isPrimary && !$this->isAutoIncrement) {
            $sql .= ' NOT NULL';
        }
        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . $this->formatDefault($this->defaultValue);
        }
        if ($this->isUnique) {
            $sql .= ' UNIQUE';
        }

        return $sql;
    }

    private function formatDefault(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}
