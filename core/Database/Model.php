<?php

declare(strict_types=1);

namespace Ds\Database;

abstract class Model
{
    protected $table = null;
    protected $primaryKey = 'id';
    protected $fillable = null;
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $key): mixed { return $this->getAttribute($key); }
    public function __set(string $key, mixed $value): void { $this->attributes[$key] = $value; }
    public function __isset(string $key): bool { return isset($this->attributes[$key]); }

    public static function query(): QueryBuilder
    {
        $model = new static();
        return db()->table($model->resolveTable());
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    /** @return static[] Hydrated models; all() remains array-compatible. */
    public static function allModels(): array
    {
        return array_map(static fn (array $row): static => new static($row), static::all());
    }

    public static function findModel(int|string $id): ?static
    {
        $row = static::find($id);
        return $row === null ? null : new static($row);
    }

    public function hasMany(string $relatedModel, string $foreignKey, ?string $localKey = null): QueryBuilder
    {
        $localKey ??= (string) $this->primaryKey;
        $value = $this->getAttribute($localKey);
        if ($value === null) {
            throw new \LogicException("Cannot resolve hasMany relation without local key [{$localKey}].");
        }
        return $relatedModel::query()->where($foreignKey, $value);
    }

    public function hasOne(string $relatedModel, string $foreignKey, ?string $localKey = null): QueryBuilder
    {
        return $this->hasMany($relatedModel, $foreignKey, $localKey)->limit(1);
    }

    public function belongsTo(string $relatedModel, string $foreignKey, ?string $ownerKey = null): QueryBuilder
    {
        $ownerKey ??= 'id';
        $value = $this->getAttribute($foreignKey);
        if ($value === null) {
            throw new \LogicException("Cannot resolve belongsTo relation without foreign key [{$foreignKey}].");
        }
        return $relatedModel::query()->where($ownerKey, $value);
    }

    public static function where(
        string $column,
        mixed $operatorOrValue,
        mixed $value = null
    ): QueryBuilder {
        return func_num_args() === 2
            ? static::query()->where($column, $operatorOrValue)
            : static::query()->where($column, $operatorOrValue, $value);
    }

    public static function orWhere(
        string $column,
        mixed $operatorOrValue,
        mixed $value = null
    ): QueryBuilder {
        return func_num_args() === 2
            ? static::query()->orWhere($column, $operatorOrValue)
            : static::query()->orWhere($column, $operatorOrValue, $value);
    }

    public static function count(): int
    {
        return static::query()->count();
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
        $primaryKey = (string) $model->primaryKey;
        // Resolve the identifier before applying fillable filtering. Primary
        // keys are commonly guarded, but a supplied id must still select update.
        $id = $attributes[$primaryKey] ?? null;
        $attributes = $model->filterFillable($attributes);

        if ($id === null) {
            if ($attributes === []) {
                return 0;
            }
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

        // A fillable whitelist may remove every requested field. Treat that as
        // a safe no-op instead of forwarding an empty SET clause to the builder.
        if ($attributes === []) {
            return 0;
        }

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

        if (in_array($snake, ['people', 'children', 'data'], true)) {
            return $snake;
        }

        if (preg_match('/[^aeiou]y$/', $snake) === 1) {
            return substr($snake, 0, -1) . 'ies';
        }

        return $snake . 's';
    }

    private function filterFillable(array $attributes): array
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
