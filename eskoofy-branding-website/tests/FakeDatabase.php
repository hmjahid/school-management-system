<?php
declare(strict_types=1);

namespace Tests;

use App\Core\DatabaseInterface;

/**
 * In-memory database fake for unit tests implementing the injected
 * DatabaseInterface. Uses a small WHERE-clause matcher so LicenseManager
 * queries (id, license_key, license_id + domain) behave like MySQL.
 * No real database is required.
 */
class FakeDatabase implements DatabaseInterface
{
    public array $tables = [];

    private int $nextId = 1;

    public function seed(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            $this->tables[$table] = $this->tables[$table] ?? [];
            $this->tables[$table][] = ['id' => $this->nextId] + $row;
            $this->nextId++;
        }
    }

    public function rows(string $table): array
    {
        return $this->tables[$table] ?? [];
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $rows = $this->select($sql, $params);

        if (empty($rows)) {
            return null;
        }

        if (stripos($sql, 'COUNT(*)') !== false) {
            return ['c' => count($rows), 'total' => count($rows)];
        }

        return $rows[0];
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->select($sql, $params);
    }

    public function insert(string $table, array $data): int
    {
        $this->tables[$table] = $this->tables[$table] ?? [];
        $data['id'] = $data['id'] ?? $this->nextId;
        $this->tables[$table][] = $data;
        $this->nextId++;

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

        return $updated;
    }

    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $rows = $this->tables[$table] ?? [];

        return count(array_filter($rows, fn (array $row) => $this->matchesWhere($where, $row, $params)));
    }

    private function select(string $sql, array $params): array
    {
        $table = $this->fromTable($sql);
        $rows = $this->tables[$table] ?? [];
        $where = $this->whereClause($sql);
        $out = [];

        foreach ($rows as $row) {
            $match = true;
            $paramIdx = 0;

            if ($where !== null && $where !== '') {
                foreach (preg_split('/\s+AND\s+/i', $where) as $predicate) {
                    if (!$this->matchesPredicate($predicate, $row, $params, $paramIdx)) {
                        $match = false;
                        break;
                    }
                }
            }

            if ($match) {
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

    /**
     * Evaluate one predicate, consuming query params in order.
     */
    private function matchesPredicate(string $predicate, array $row, array $params, int &$paramIdx): bool
    {
        $predicate = trim((string) preg_replace('/`/u', '', $predicate));

        // col = ? / col != ? / col LIKE ?
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

        // col = 'literal' / col != 'literal'
        if (preg_match("/^([a-z_]+)\s*(=|!=)\s*'((?:[^'\\\\]|\\\\.)*)'$/i", $predicate, $m)) {
            $value = stripcslashes($m[3]);
            $actual = $row[$m[1]] ?? null;

            return $m[2] === '=' ? $actual === $value : $actual !== $value;
        }

        // col = bare / col != bare (numeric, identifier)
        if (preg_match('/^([a-z_]+)\s*(=|!=)\s*([a-z0-9_\-]+)$/i', $predicate, $m)) {
            $value = $m[3];
            $actual = $row[$m[1]] ?? null;
            if (is_numeric($value)) {
                $actual = is_numeric($actual) ? (float) $actual : null;
                $value = (float) $value;
            }

            return $m[2] === '=' ? $actual === $value : $actual !== $value;
        }

        // c.col = 'literal' / c.col = ? (joined-table literal)
        if (preg_match("/^([a-z_]+)\.([a-z_]+)\s*(=|!=)\s*'((?:[^'\\\\]|\\\\.)*)'$/i", $predicate, $m)) {
            $value = stripcslashes($m[4]);
            $actual = $row[$m[2]] ?? null;

            return $m[3] === '=' ? $actual === $value : $actual !== $value;
        }
        if (preg_match('/^([a-z_]+)\.([a-z_]+)\s*(=|!=|LIKE)\s*\?$/i', $predicate, $m)) {
            $value = $params[$paramIdx] ?? null;
            $paramIdx++;
            $actual = $row[$m[2]] ?? null;

            if (strcasecmp($m[3], 'LIKE') === 0) {
                $like = str_replace('%', '', (string) $value);

                return str_contains((string) $actual, $like);
            }

            return $m[3] === '=' ? $actual === $value : $actual !== $value;
        }

        // col IS [NOT] NULL — with optional table qualifier.
        if (preg_match('/^([a-z_]+)\.([a-z_]+)\s+IS NULL$/i', $predicate, $m)) {
            return empty($row[$m[2]]);
        }
        if (preg_match('/^([a-z_]+)\.([a-z_]+)\s+IS NOT NULL$/i', $predicate, $m)) {
            return !empty($row[$m[2]]);
        }
        if (preg_match('/^([a-z_]+)\s+IS NULL$/i', $predicate, $m)) {
            return empty($row[$m[1]]);
        }
        if (preg_match('/^([a-z_]+)\s+IS NOT NULL$/i', $predicate, $m)) {
            return !empty($row[$m[1]]);
        }

        // col <=> ? (null-safe equality)
        if (preg_match('/^([a-z_]+)\s*<=>\s*\?$/i', $predicate, $m)) {
            $value = $params[$paramIdx] ?? null;
            $paramIdx++;

            return ($row[$m[1]] ?? null) === $value;
        }

        // Unsupported predicate: assume match to keep tests focused.
        return true;
    }

    private function matchesWhere(string $where, array $row, array &$params): bool
    {
        $where = trim($where);
        if ($where === '' || $where === '1=1') {
            return true;
        }

        $paramIdx = 0;
        foreach (preg_split('/\s+AND\s+/i', $where) as $predicate) {
            if (!$this->matchesPredicate($predicate, $row, $params, $paramIdx)) {
                return false;
            }
        }

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
        if (!preg_match('/WHERE\s+(.+?)(?:ORDER BY|LIMIT|$)/is', $sql, $m)) {
            return null;
        }

        return trim($m[1]);
    }
}