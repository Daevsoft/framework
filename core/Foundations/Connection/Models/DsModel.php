<?php

declare(strict_types=1);

namespace Ds\Foundations\Connection\Models;

use Ds\Database\Model;

/**
 * Transitional adapter for the v3 DsModel base class.
 * Execution is delegated to the v4 PDO model/query builder.
 */
abstract class DsModel extends Model
{
    public $table = null;
    protected $primaryKey = 'id';
    protected $fillable = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (!is_string($this->table) || $this->table === '') {
            $class = (new \ReflectionClass($this))->getShortName();
            $class = preg_replace('/Model$/', '', $class) ?? $class;
            $this->table = strtolower($class);
        }
    }

    public static function all(array|string $columns = []): array
    {
        $query = static::query();
        if ($columns !== [] && $columns !== '') {
            $query->select($columns);
        }
        return $query->get();
    }

    public static function find(int|string $id, array|string $columns = '*'): ?array
    {
        $model = new static();
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->where((string) $model->primaryKey, $id)->first();
    }

    public static function findBy(string $column, mixed $value, array|string $columns = '*'): ?array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->where($column, $value)->first();
    }

    public static function findsBy(string $column, mixed $value, array|string $columns = '*'): array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->where($column, $value)->get();
    }

    public static function findsWhere(array $conditions, array|string $columns = '*'): array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        foreach ($conditions as $column => $value) {
            $query->where((string) $column, $value);
        }
        return $query->get();
    }

    public static function findIsNull(string $column, array|string $columns = '*'): ?array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->whereNull($column)->first();
    }

    public static function save(array $attributes): int
    {
        $model = new static();
        $primaryKey = (string) $model->primaryKey;

        if (array_is_list($attributes)) {
            $affected = 0;
            foreach ($attributes as $row) {
                if (!is_array($row)) {
                    throw new \InvalidArgumentException('Bulk model save expects arrays of attributes.');
                }
                $affected += static::save($row);
            }
            return $affected;
        }

        $id = $attributes[$primaryKey] ?? null;
        unset($attributes[$primaryKey]);
        $filtered = $model->filterLegacyFillable($attributes);

        if ($id !== null) {
            if ($filtered === []) {
                return 0;
            }
            return static::query()->where($primaryKey, $id)->update($filtered);
        }

        if ($filtered === []) {
            return 0;
        }

        return static::query()->insert($filtered);
    }

    public static function remove(int|array $idWhere): int
    {
        $model = new static();
        $query = static::query();
        if (is_array($idWhere)) {
            foreach ($idWhere as $column => $value) {
                $query->where((string) $column, $value);
            }
        } else {
            $query->where((string) $model->primaryKey, $idWhere);
        }
        return $query->delete();
    }

    public function filledFields(array $fields): array
    {
        return $this->filterLegacyFillable($fields);
    }

    public static function initiateClass(): static
    {
        return new static();
    }

    private function filterLegacyFillable(array $attributes): array
    {
        if ($this->fillable === null) {
            return $attributes;
        }
        if (!is_array($this->fillable) || $this->fillable === []) {
            return [];
        }
        return array_intersect_key($attributes, array_fill_keys($this->fillable, true));
    }
}
