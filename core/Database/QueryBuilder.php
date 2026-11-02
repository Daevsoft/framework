<?php

declare(strict_types=1);

namespace Ds\Database;

final class QueryBuilder
{
    private array $columns = ['*'];
    private array $conditions = [];
    private array $bindings = [];
    private array $orders = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    public function __construct(
        private readonly DatabaseManager $database,
        private readonly string $table
    ) {
        $this->database->quoteIdentifier($table);
    }

    public function select(array|string $columns = '*'): self
    {
        $columns = is_array($columns) ? $columns : explode(',', $columns);
        $this->columns = array_map(
            fn (string $column): string => $this->database->quoteIdentifier(trim($column)),
            $columns
        );
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = strtoupper(trim((string) $operatorOrValue));
        }

        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'], true)) {
            throw new \InvalidArgumentException("Unsupported SQL comparison operator [{$operator}].");
        }

        $parameter = ':where_' . count($this->bindings);
        $this->conditions[] = $this->database->quoteIdentifier($column) . " {$operator} {$parameter}";
        $this->bindings[$parameter] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->conditions[] = '1 = 0';
            return $this;
        }

        $parameters = [];
        foreach ($values as $value) {
            $parameter = ':where_' . count($this->bindings);
            $parameters[] = $parameter;
            $this->bindings[$parameter] = $value;
        }

        $this->conditions[] = $this->database->quoteIdentifier($column)
            . ' IN (' . implode(', ', $parameters) . ')';

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->conditions[] = $this->database->quoteIdentifier($column) . ' IS NULL';
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('Order direction must be ASC or DESC.');
        }

        $this->orders[] = $this->database->quoteIdentifier($column) . ' ' . $direction;
        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Query limit cannot be negative.');
        }
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Query offset cannot be negative.');
        }
        $this->offsetValue = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = 'SELECT ' . implode(', ', $this->columns)
            . ' FROM ' . $this->database->quoteIdentifier($this->table)
            . $this->whereSql();

        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }
        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $this->database->select($sql, $this->bindings);
    }

    public function first(): ?array
    {
        $query = clone $this;
        $query->limit(1);
        return $query->get()[0] ?? null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM ' . $this->database->quoteIdentifier($this->table)
            . $this->whereSql();
        $row = $this->database->select($sql, $this->bindings)[0] ?? [];
        return (int) ($row['aggregate'] ?? 0);
    }

    public function insert(array $values): int
    {
        if ($values === []) {
            throw new \InvalidArgumentException('Insert values cannot be empty.');
        }

        $columns = [];
        $parameters = [];
        $bindings = [];
        foreach ($values as $column => $value) {
            $columns[] = $this->database->quoteIdentifier((string) $column);
            $parameter = ':insert_' . count($bindings);
            $parameters[] = $parameter;
            $bindings[$parameter] = $value;
        }

        return $this->database->execute(
            'INSERT INTO ' . $this->database->quoteIdentifier($this->table)
                . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $parameters) . ')',
            $bindings
        );
    }

    public function update(array $values): int
    {
        if ($values === []) {
            throw new \InvalidArgumentException('Update values cannot be empty.');
        }

        $sets = [];
        $bindings = [];
        foreach ($values as $column => $value) {
            $parameter = ':set_' . count($bindings);
            $sets[] = $this->database->quoteIdentifier((string) $column) . ' = ' . $parameter;
            $bindings[$parameter] = $value;
        }

        return $this->database->execute(
            'UPDATE ' . $this->database->quoteIdentifier($this->table)
                . ' SET ' . implode(', ', $sets) . $this->whereSql(),
            $bindings + $this->bindings
        );
    }

    public function delete(): int
    {
        return $this->database->execute(
            'DELETE FROM ' . $this->database->quoteIdentifier($this->table) . $this->whereSql(),
            $this->bindings
        );
    }

    private function whereSql(): string
    {
        return $this->conditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->conditions);
    }
}
