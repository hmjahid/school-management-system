<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Fluent query builder. When bound to a model class (via Model::query()) it
 * hydrates results into model instances that also behave like arrays, so both
 * the ported Blade views and the existing raw-PHP controllers work.
 */
class QueryBuilder
{
    private string $table;
    private string $primaryKey;
    private string $where = '1=1';
    private array $whereParams = [];
    private string $orderBy = '';
    private int $limit = 0;
    private int $offset = 0;
    private array $selectColumns = ['*'];
    private array $joins = [];
    private array $groupBy = [];
    private string $having = '';
    private array $havingParams = [];
    private ?string $modelClass = null;
    /** @var list<string> */
    private array $with = [];

    public function __construct(string $table, string $primaryKey = 'id')
    {
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    public function setModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function select(string ...$columns): self
    {
        $this->selectColumns = $columns;
        return $this;
    }

    public function addSelect(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->selectColumns[] = $column;
        }
        return $this;
    }

    public function distinct(): self
    {
        return $this;
    }

    public function where(string $column, mixed $value, mixed $op = '='): self
    {
        if (is_string($value) && self::isOperator($value)) {
            // Laravel-style where($col, $op, $val)
            $real = $op;
            $op = $value;
            $value = $real;
        }
        $this->where .= " AND {$column} {$op} ?";
        $this->whereParams[] = $value;
        return $this;
    }

    public static function isOperator(string $value): bool
    {
        return in_array(strtoupper($value), [
            '=', '<', '>', '<=', '>=', '!=', '<>', '<=>',
            'LIKE', 'NOT LIKE', 'ILIKE', 'RLIKE', 'IN', 'NOT IN',
        ], true);
    }

    public function whereRaw(string $sql, array $params = []): self
    {
        $this->where .= " AND ({$sql})";
        array_push($this->whereParams, ...$params);
        return $this;
    }

    public function orWhere(string $column, mixed $value, mixed $op = '='): self
    {
        if (is_string($value) && self::isOperator($value)) {
            $real = $op;
            $op = $value;
            $value = $real;
        }
        $this->where .= " OR {$column} {$op} ?";
        $this->whereParams[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->where .= ' AND 1=0';
            return $this;
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where .= " AND {$column} IN ({$placeholders})";
        array_push($this->whereParams, ...$values);
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        if ($values === []) {
            return $this;
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where .= " AND {$column} NOT IN ({$placeholders})";
        array_push($this->whereParams, ...$values);
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where .= " AND {$column} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where .= " AND {$column} IS NOT NULL";
        return $this;
    }

    public function whereDate(string $column, string $value, string $op = '='): self
    {
        $this->where .= " AND DATE({$column}) {$op} ?";
        $this->whereParams[] = $value;
        return $this;
    }

    public function whereBetween(string $column, mixed $start, mixed $end): self
    {
        $this->where .= " AND {$column} BETWEEN ? AND ?";
        $this->whereParams[] = $start;
        $this->whereParams[] = $end;
        return $this;
    }

    public function between(string $column, mixed $start, mixed $end): self
    {
        return $this->whereBetween($column, $start, $end);
    }

    public function when(mixed $value, callable $callback, ?callable $default = null): self
    {
        if ($value) {
            $callback($this, $value);
        } elseif ($default) {
            $default($this, $value);
        }
        return $this;
    }

    public function whereHas(string $relation, ?callable $callback = null): self
    {
        // Eager relation filtering is not supported by the raw SQL builder;
        // kept for API compatibility so Blade controllers do not fatal.
        return $this;
    }

    public function has(string $relation, string $operator = '>=', int $count = 1): self
    {
        return $this;
    }

    public function with(string|array $relations): self
    {
        $this->with = array_merge($this->with, (array) $relations);
        return $this;
    }

    public function withCount(string|array $relations): self
    {
        return $this;
    }

    public function join(string $table, string $col1, string $col2, string $type = 'INNER'): self
    {
        $this->joins[] = "{$type} JOIN {$table} ON {$col1} = {$col2}";
        return $this;
    }

    public function leftJoin(string $table, string $col1, string $col2): self
    {
        return $this->join($table, $col1, $col2, 'LEFT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "ORDER BY {$column} {$direction}";
        return $this;
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function orderByRaw(string $sql): self
    {
        $this->orderBy = "ORDER BY {$sql}";
        return $this;
    }

    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function take(int $limit): self
    {
        return $this->limit($limit);
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function skip(int $offset): self
    {
        return $this->offset($offset);
    }

    public function groupBy(string ...$columns): self
    {
        $this->groupBy = $columns;
        return $this;
    }

    public function having(string $sql, array $params = []): self
    {
        $this->having = $sql;
        $this->havingParams = $params;
        return $this;
    }

    private function buildSql(): string
    {
        $select = implode(', ', $this->selectColumns);
        $sql = "SELECT {$select} FROM {$this->table}";
        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        $sql .= " WHERE {$this->where}";
        if ($this->groupBy) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        if ($this->having) {
            $sql .= " HAVING {$this->having}";
        }
        if ($this->orderBy) {
            $sql .= " {$this->orderBy}";
        }
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }
        return $sql;
    }

    private function getParams(): array
    {
        return array_merge($this->whereParams, $this->havingParams);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,mixed>
     */
    private function hydrate(array $rows): array
    {
        if ($this->modelClass === null) {
            return $rows;
        }
        $class = $this->modelClass;
        return array_map(static fn ($row) => $class::newFromRow($row), $rows);
    }

    public function get(array $columns = []): array
    {
        if ($columns !== []) {
            $this->selectColumns = $columns;
        }
        $rows = Database::getInstance()->fetchAll($this->buildSql(), $this->getParams());
        return $this->hydrate($rows);
    }

    public function first(): mixed
    {
        $this->limit = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function firstOrFail(): mixed
    {
        $result = $this->first();
        if ($result === null) {
            throw new \RuntimeException('No query results for model ' . ($this->modelClass ?? $this->table));
        }
        return $result;
    }

    public function count(): int
    {
        $clone = clone $this;
        $clone->selectColumns = ['COUNT(*) as cnt'];
        $clone->limit = 0;
        $clone->offset = 0;
        $row = $clone->get();
        $first = $row[0] ?? null;
        return (int) ($first['cnt'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function sum(string $column): int|float
    {
        return (int) ($this->aggregate('SUM', $column) ?? 0);
    }

    public function avg(string $column): ?float
    {
        $value = $this->aggregate('AVG', $column);
        return $value === null ? null : (float) $value;
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    private function aggregate(string $fn, string $column): mixed
    {
        $clone = clone $this;
        $clone->selectColumns = ["{$fn}({$column}) as aggregate"];
        $clone->limit = 0;
        $clone->offset = 0;
        $row = $clone->get();
        return $row[0]['aggregate'] ?? null;
    }

    public function value(string $column): mixed
    {
        $clone = clone $this;
        $clone->selectColumns = [$column];
        $clone->limit = 1;
        $row = $clone->get();
        return $row[0][$column] ?? null;
    }

    public function pluck(string $column, string $key = ''): array
    {
        $rows = $this->get();
        $result = [];
        foreach ($rows as $row) {
            $k = $key ? ($row[$key] ?? null) : null;
            $result[$k ?? count($result)] = $row[$column] ?? null;
        }
        return $result;
    }

    public function chunk(int $size, callable $callback): bool
    {
        $page = 1;
        do {
            $clone = clone $this;
            $clone->limit = $size;
            $clone->offset = ($page - 1) * $size;
            $results = $clone->get();
            if ($results === []) {
                break;
            }
            if ($callback($results, $page) === false) {
                return false;
            }
            $page++;
        } while (count($results) === $size);
        return true;
    }

    public function each(callable $callback): bool
    {
        return $this->chunk(1000, static function ($results) use ($callback) {
            foreach ($results as $key => $item) {
                if ($callback($item, $key) === false) {
                    return false;
                }
            }
        });
    }

    public function update(array $data): int
    {
        return Database::getInstance()->update($this->table, $data, $this->where, $this->whereParams);
    }

    public function delete(): int
    {
        return Database::getInstance()->delete($this->table, $this->where, $this->whereParams);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->count();
        $lastPage = (int) ceil($total / max(1, $perPage));
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        $data = $this->get();
        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
        ];
    }

    /**
     * Forward unknown methods to the bound model's local scopes (scopeX).
     */
    public function __call(string $method, array $parameters): mixed
    {
        if ($this->modelClass !== null) {
            $instance = new $this->modelClass();
            $scope = 'scope' . ucfirst($method);
            if (method_exists($instance, $scope)) {
                $result = $instance->{$scope}($this, ...$parameters);
                return $result instanceof self ? $result : $this;
            }
        }
        throw new \BadMethodCallException('Call to undefined query method ' . static::class . '::' . $method);
    }
}
