<?php

declare(strict_types=1);

namespace Ds\Database;

final class QueryBuilder
{
    private array $columns = ['*'];
    private array $conditions = [];
    private array $bindings = [];
    private array $orders = [];
    private array $joins = [];
    private array $groups = [];
    private array $havingConditions = [];
    private bool $distinctValue = false;
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

    public function join(string $table, string $first, string $operator, string $second): self
    {
        return $this->addJoin('INNER', $table, $first, $operator, $second);
    }

    public function innerJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->addJoin('INNER', $table, $first, $operator, $second);
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->addJoin('LEFT', $table, $first, $operator, $second);
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->addJoin('RIGHT', $table, $first, $operator, $second);
    }

    private function addJoin(string $type, string $table, string $first, string $operator, string $second): self
    {
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<='], true)) {
            throw new \InvalidArgumentException("Unsupported SQL join operator [{$operator}].");
        }
        $this->joins[] = $type . ' JOIN ' . $this->database->quoteIdentifier($table)
            . ' ON ' . $this->database->quoteIdentifier($first) . " {$operator} "
            . $this->database->quoteIdentifier($second);
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->distinctValue = $distinct;
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

        if ($value === null && in_array($operator, ['=', '!=', '<>'], true)) {
            $this->conditions[] = $this->database->quoteIdentifier($column)
                . (in_array($operator, ['!=', '<>'], true) ? ' IS NOT NULL' : ' IS NULL');
            return $this;
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

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        $before = count($this->conditions);
        if (func_num_args() === 2) {
            $this->where($column, $operatorOrValue);
        } else {
            $this->where($column, $operatorOrValue, $value);
        }
        if (count($this->conditions) > $before) {
            $this->conditions[$before] = 'OR ' . $this->conditions[$before];
        }
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        if ($values === []) {
            return $this;
        }
        $parameters = [];
        foreach ($values as $value) {
            $parameter = ':where_' . count($this->bindings);
            $parameters[] = $parameter;
            $this->bindings[$parameter] = $value;
        }
        $this->conditions[] = $this->database->quoteIdentifier($column)
            . ' NOT IN (' . implode(', ', $parameters) . ')';
        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        $before = count($this->conditions);
        $this->whereIn($column, $values);
        if (count($this->conditions) > $before) {
            $this->conditions[$before] = 'OR ' . $this->conditions[$before];
        }
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->conditions[] = $this->database->quoteIdentifier($column) . ' IS NOT NULL';
        return $this;
    }

    public function groupBy(array|string $columns): self
    {
        $columns = is_array($columns) ? $columns : explode(',', $columns);
        foreach ($columns as $column) {
            $this->groups[] = $this->database->quoteIdentifier(trim((string) $column));
        }
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'], true)) {
            throw new \InvalidArgumentException("Unsupported SQL having operator [{$operator}].");
        }
        $parameter = ':having_' . count($this->bindings);
        $this->havingConditions[] = $this->database->quoteIdentifier($column) . " {$operator} {$parameter}";
        $this->bindings[$parameter] = $value;
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
        $sql = 'SELECT ' . ($this->distinctValue ? 'DISTINCT ' : '') . implode(', ', $this->columns)
            . ' FROM ' . $this->database->quoteIdentifier($this->table)
            . ($this->joins === [] ? '' : ' ' . implode(' ', $this->joins))
            . $this->whereSql();

        if ($this->groups !== []) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        if ($this->havingConditions !== []) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havingConditions);
        }
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
            . ($this->joins === [] ? '' : ' ' . implode(' ', $this->joins))
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
        if ($this->conditions === []) {
            return '';
        }

        $sql = '';
        foreach ($this->conditions as $condition) {
            if ($sql === '') {
                $sql = preg_replace('/^OR /', '', $condition) ?? $condition;
            } elseif (str_starts_with($condition, 'OR ')) {
                $sql .= ' OR ' . substr($condition, 3);
            } else {
                $sql .= ' AND ' . $condition;
            }
        }
        return ' WHERE ' . $sql;
    }
}
