<?php

declare(strict_types=1);

namespace Ds\Database;

abstract class Model
{
    protected $table = null;
    protected $primaryKey = 'id';
    protected $fillable = [];

    public static function query(): QueryBuilder
    {
        $model = new static();
        return db()->table($model->resolveTable());
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(int|string $id): ?array
    {
        $model = new static();
        return db()->table($model->resolveTable())
            ->where((string) $model->primaryKey, $id)
            ->first();
    }

    public static function create(array $attributes): int
    {
        return static::save($attributes);
    }

    /**
     * Insert when no primary key is supplied; otherwise update the matching row.
     * Only explicitly declared fillable fields are accepted when a fillable list exists.
     */
    public static function save(array $attributes): int
    {
        $model = new static();
        $attributes = $model->filterFillable($attributes);
        $primaryKey = (string) $model->primaryKey;
        $id = $attributes[$primaryKey] ?? null;

        if ($id === null) {
            return db()->table($model->resolveTable())->insert($attributes);
        }

        unset($attributes[$primaryKey]);
        if ($attributes === []) {
            return 0;
        }

        return db()->table($model->resolveTable())
            ->where($primaryKey, $id)
            ->update($attributes);
    }

    public static function updateById(int|string $id, array $attributes): int
    {
        $model = new static();
        $attributes = $model->filterFillable($attributes);
        unset($attributes[(string) $model->primaryKey]);

        return db()->table($model->resolveTable())
            ->where((string) $model->primaryKey, $id)
            ->update($attributes);
    }

    public static function deleteById(int|string $id): int
    {
        $model = new static();
        return db()->table($model->resolveTable())
            ->where((string) $model->primaryKey, $id)
            ->delete();
    }

    private function resolveTable(): string
    {
        if (is_string($this->table) && $this->table !== '') {
            return $this->table;
        }

        $parts = explode('\\', static::class);
        $class = end($parts);
        $class = preg_replace('/Model$/', '', $class) ?? $class;
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $class));

        return $snake . 's';
    }

    private function filterFillable(array $attributes): array
    {
        if (!is_array($this->fillable) || $this->fillable === []) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_fill_keys($this->fillable, true));
    }
}
