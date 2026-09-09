<?php
declare(strict_types=1);

namespace App\Core;

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

    public function __construct(string $table, string $primaryKey = 'id')
    {
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    public function select(string ...$columns): self
    {
        $this->selectColumns = $columns;
        return $this;
    }

    public function where(string $column, mixed $value, string $op = '='): self
    {
        $this->where .= " AND {$column} {$op} ?";
        $this->whereParams[] = $value;
        return $this;
    }

    public function whereRaw(string $sql, array $params = []): self
    {
        $this->where .= " AND ({$sql})";
        array_push($this->whereParams, ...$params);
        return $this;
    }

    public function orWhere(string $column, mixed $value, string $op = '='): self
    {
        $this->where .= " OR {$column} {$op} ?";
        $this->whereParams[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where .= " AND {$column} IN ({$placeholders})";
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

    public function between(string $column, mixed $start, mixed $end): self
    {
        $this->where .= " AND {$column} BETWEEN ? AND ?";
        $this->whereParams[] = $start;
        $this->whereParams[] = $end;
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

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
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
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
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

    public function get(): array
    {
        return Database::getInstance()->fetchAll($this->buildSql(), $this->getParams());
    }

    public function first(): ?array
    {
        $this->limit = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $this->selectColumns = ['COUNT(*) as cnt'];
        $row = $this->first();
        return (int) ($row['cnt'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function value(string $column): mixed
    {
        $this->selectColumns = [$column];
        $row = $this->first();
        return $row[$column] ?? null;
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

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->count();
        $lastPage = (int) ceil($total / $perPage);
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        $data = $this->get();
        return [
            'data'       => $data,
            'total'      => $total,
            'per_page'   => $perPage,
            'current_page' => $page,
            'last_page'  => $lastPage,
        ];
    }
}
