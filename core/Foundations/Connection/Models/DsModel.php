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

    /** Start a legacy-style select chain while keeping the v4 QueryBuilder underneath. */
    public function select(array|string $columns = '*', ?string $table = null): \Ds\Database\QueryBuilder
    {
        $query = $table === null ? static::query() : \db()->table($table);
        return $query->select($columns);
    }

    public function get_object(): array
    {
        return static::query()->get_object();
    }

    public function get_array(): array
    {
        return static::query()->get_array();
    }

    public function row(): ?array
    {
        return static::query()->row();
    }

    public static function first(array|string $columns = '*'): ?array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->first();
    }

    public function asc(string $column): \Ds\Database\QueryBuilder
    {
        return static::query()->orderBy($column, 'ASC');
    }

    public function desc(string $column): \Ds\Database\QueryBuilder
    {
        return static::query()->orderBy($column, 'DESC');
    }

    public function groupBy(array|string $columns): \Ds\Database\QueryBuilder
    {
        return static::query()->groupBy($columns);
    }

    public function distinct(array|string $columns = '*'): \Ds\Database\QueryBuilder
    {
        return static::query()->distinct()->select($columns);
    }

    public function join(string $table, string $first, string $operator, string $second, ?string $alias = null): \Ds\Database\QueryBuilder
    {
        return static::query()->join($table, $first, $operator, $second, $alias);
    }

    public function leftJoin(string $table, string $first, string $operator, string $second, ?string $alias = null): \Ds\Database\QueryBuilder
    {
        return static::query()->leftJoin($table, $first, $operator, $second, $alias);
    }

    public function rightJoin(string $table, string $first, string $operator, string $second, ?string $alias = null): \Ds\Database\QueryBuilder
    {
        return static::query()->rightJoin($table, $first, $operator, $second, $alias);
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

    public static function findIsNotNull(string $column, array|string $columns = '*'): ?array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->whereNotNull($column)->first();
    }

    public static function findsIsNull(string $column, array|string $columns = '*'): array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->whereNull($column)->get();
    }

    public static function findsIsNotNull(string $column, array|string $columns = '*'): array
    {
        $query = static::query();
        if ($columns !== '*') {
            $query->select($columns);
        }
        return $query->whereNotNull($column)->get();
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

    public function whereIn(string $column, array $values): \Ds\Database\QueryBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    public function whereNotIn(string $column, array $values): \Ds\Database\QueryBuilder
    {
        return static::query()->whereNotIn($column, $values);
    }

    public function orWhereIn(string $column, array $values): \Ds\Database\QueryBuilder
    {
        return static::query()->orWhereIn($column, $values);
    }

    public function like(string $column, mixed $value): \Ds\Database\QueryBuilder
    {
        return static::query()->where($column, 'LIKE', $value);
    }

    public function orLike(string $column, mixed $value): \Ds\Database\QueryBuilder
    {
        return static::query()->orWhere($column, 'LIKE', $value);
    }

    public function equal(string $column, mixed $value): \Ds\Database\QueryBuilder
    {
        return static::query()->where($column, '=', $value);
    }

    public function not_equal(string $column, mixed $value): \Ds\Database\QueryBuilder
    {
        return static::query()->where($column, '!=', $value);
    }

    public function filledFields(array $fields): array
    {
        return $this->filterLegacyFillable($fields);
    }

    public static function initiateClass(): static
    {
        return new static();
    }

    protected function size(?array $where = null): int
    {
        $query = static::query();
        foreach ($where ?? [] as $column => $value) {
            $query->where((string) $column, $value);
        }
        return $query->count();
    }

    /**
     * Legacy pagination payload backed by the v4 QueryBuilder.
     * Query parameters are preserved and page numbers are clamped to valid bounds.
     */
    protected function paginate(int $limit = 10): array
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Pagination limit must be greater than zero.');
        }

        $request = \Ds\Context\Context::default()->get('request');
        $queryParams = $request instanceof \Ds\Http\Request ? $request->query : [];
        $requestedPage = $request instanceof \Ds\Http\Request
            ? (int) $request->getQuery('page', 1)
            : 1;
        $size = $this->size();
        $lastPage = max(1, (int) ceil($size / $limit));
        $currentPage = min(max(1, $requestedPage), $lastPage);
        $start = ($currentPage - 1) * $limit;
        $path = $request instanceof \Ds\Http\Request ? $request->getPath() : '';

        $data = static::query()->limit($limit)->offset($start)->get();
        $link = static function (int $page) use ($path, $queryParams): string {
            $params = $queryParams;
            $params['page'] = $page;
            return $path . '?' . http_build_query($params);
        };

        return [
            'data' => $data,
            'links' => [
                'prev' => $currentPage <= 1 ? null : $link($currentPage - 1),
                'next' => $currentPage >= $lastPage ? null : $link($currentPage + 1),
                'first' => $link(1),
                'last' => $link($lastPage),
            ],
            'meta' => [
                'current_page' => $currentPage,
                'from' => $start,
                'last_page' => $lastPage,
                'path' => $path,
                'per_page' => $limit,
                'to' => $size,
                'total' => $size,
            ],
        ];
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
