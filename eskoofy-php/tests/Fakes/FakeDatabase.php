<?php
declare(strict_types=1);

namespace Tests\Fakes;

use App\Core\DatabaseInterface;

/**
 * In-memory database fake for the front-controller integration test.
 *
 * Tables are stored as `tables[$tableName] = [row, row, ...]`. WHERE clauses
 * are matched with a small SQL-subset parser good enough for the public-site
 * controllers (column = 'literal', column = ?, IS NULL, AND, OR). It is NOT a
 * full SQL implementation — only the predicates the public routes use.
 *
 * This is dev-only. The real {@see \App\Core\Database} is the production
 * implementation; the fake exists so the front controller can be exercised
 * in `composer test` without a real MySQL connection.
 */
class FakeDatabase implements DatabaseInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $tables = [];

    private int $nextId = 1;

    /** @var array<int, array{action: string, sql: string, params: array}> */
    public array $log = [];

    public function seed(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            $this->tables[$table][] = $row;
        }
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $this->log[] = ['action' => 'query', 'sql' => $sql, 'params' => $params];
        return new FakeStatement(array_merge($this->fetchAll($sql, $params), [$params]));
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $this->log[] = ['action' => 'fetch', 'sql' => $sql, 'params' => $params];
        $rows = $this->select($sql, $params);
        if (empty($rows)) {
            return null;
        }
        if (stripos($sql, 'COUNT(*)') !== false) {
            return ['c' => count($rows), 'cnt' => count($rows), 'total' => count($rows)];
        }
        return $rows[0];
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $this->log[] = ['action' => 'fetchAll', 'sql' => $sql, 'params' => $params];
        return $this->select($sql, $params);
    }

    public function insert(string $table, array $data): int
    {
        $this->tables[$table] = $this->tables[$table] ?? [];
        $data['id'] = $data['id'] ?? $this->nextId;
        $this->tables[$table][] = $data;
        $this->nextId++;
        $this->log[] = ['action' => 'insert', 'sql' => "INSERT INTO {$table}", 'params' => $data];
        return (int) $data['id'];
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $this->tables[$table] = $this->tables[$table] ?? [];
        $updated = 0;
        foreach ($this->tables[$table] as $i => $row) {
            if ($this->matchesWhere($where, $row, $whereParams)) {
                $this->tables[$table][$i] = array_merge($row, $data);
                $updated++;
            }
        }
        $this->log[] = ['action' => 'update', 'sql' => "UPDATE {$table} SET ... WHERE {$where}", 'params' => $whereParams];
        return $updated;
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $this->tables[$table] = $this->tables[$table] ?? [];
        $kept = [];
        $deleted = 0;
        foreach ($this->tables[$table] as $row) {
            if ($this->matchesWhere($where, $row, $params)) {
                $deleted++;
                continue;
            }
            $kept[] = $row;
        }
        $this->tables[$table] = $kept;
        $this->log[] = ['action' => 'delete', 'sql' => "DELETE FROM {$table} WHERE {$where}", 'params' => $params];
        return $deleted;
    }

    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $rows = $this->tables[$table] ?? [];
        $matched = 0;
        foreach ($rows as $row) {
            if ($this->matchesWhere($where, $row, $params)) {
                $matched++;
            }
        }
        return $matched;
    }

    public function beginTransaction(): void
    {
        $this->log[] = ['action' => 'beginTransaction', 'sql' => '', 'params' => []];
    }

    public function commit(): void
    {
        $this->log[] = ['action' => 'commit', 'sql' => '', 'params' => []];
    }

    public function rollBack(): void
    {
        $this->log[] = ['action' => 'rollBack', 'sql' => '', 'params' => []];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function select(string $sql, array $params): array
    {
        $table = $this->fromTable($sql);
        $rows = $this->tables[$table] ?? [];
        $where = $this->whereClause($sql);
        $out = [];

        foreach ($rows as $row) {
            if ($where === null || $where === '' || $where === '1=1') {
                $out[] = $row;
                continue;
            }
            if ($this->matchesWhere($where, $row, $params)) {
                $out[] = $row;
            }
        }

        if (preg_match('/LIMIT\s+(\d+)(?:\s+OFFSET\s+(\d+))?/i', $sql, $m)) {
            $limit = (int) $m[1];
            $offset = isset($m[2]) ? (int) $m[2] : 0;
            $out = array_slice($out, $offset, $limit);
        }

        return $out;
    }

    private function matchesWhere(string $where, array $row, array $params): bool
    {
        $where = trim($where);
        if ($where === '' || $where === '1=1') {
            return true;
        }

        // Split on AND (top-level only — controllers don't use OR in WHERE).
        $predicates = preg_split('/\s+AND\s+/i', $where);
        $paramIdx = 0;
        foreach ($predicates as $predicate) {
            if (!$this->matchesPredicate(trim($predicate), $row, $params, $paramIdx)) {
                return false;
            }
        }
        return true;
    }

    private function matchesPredicate(string $predicate, array $row, array $params, int &$paramIdx): bool
    {
        $predicate = (string) preg_replace('/`/', '', $predicate);

        // col IS NULL / IS NOT NULL
        if (preg_match('/^([a-z_]+)\s+IS NULL$/i', $predicate, $m)) {
            return empty($row[$m[1]]);
        }
        if (preg_match('/^([a-z_]+)\s+IS NOT NULL$/i', $predicate, $m)) {
            return !empty($row[$m[1]]);
        }

        // col = ? / != ? / LIKE ?
        if (preg_match('/^([a-z_]+)\s*(=|!=|LIKE)\s*\?$/i', $predicate, $m)) {
            $value = $params[$paramIdx] ?? null;
            $paramIdx++;
            $actual = $row[$m[1]] ?? null;
            if (strcasecmp($m[2], 'LIKE') === 0) {
                $like = str_replace('%', '', (string) $value);
                return str_contains((string) $actual, $like);
            }
            return $m[2] === '=' ? $actual === $value : $actual !== $value;
        }

        // col = 'literal' / != 'literal'
        if (preg_match("/^([a-z_]+)\s*(=|!=)\s*'((?:[^'\\\\]|\\\\.)*)'$/i", $predicate, $m)) {
            $value = stripcslashes($m[3]);
            $actual = $row[$m[1]] ?? null;
            return $m[2] === '=' ? $actual === $value : $actual !== $value;
        }

        // col = bare / col != bare (numeric or identifier)
        if (preg_match('/^([a-z_]+)\s*(=|!=)\s*([a-z0-9_\-]+)$/i', $predicate, $m)) {
            $value = $m[3];
            $actual = $row[$m[1]] ?? null;
            if (is_numeric($value)) {
                $actual = is_numeric($actual) ? (float) $actual : null;
                $value = (float) $value;
            }
            return $m[2] === '=' ? $actual === $value : $actual !== $value;
        }

        // Unknown predicate: treat as match (controllers should not produce these).
        return true;
    }

    private function fromTable(string $sql): string
    {
        if (preg_match('/FROM\s+([a-z_]+)/i', $sql, $m)) {
            return $m[1];
        }
        return '';
    }

    private function whereClause(string $sql): ?string
    {
        if (!preg_match('/WHERE\s+(.+?)(?:ORDER BY|LIMIT|GROUP BY|HAVING|$)/is', $sql, $m)) {
            return null;
        }
        return trim($m[1]);
    }
}