<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\DatabaseInterface;

/**
 * Scripted DatabaseInterface double for aggregate/report read models.
 *
 * The analytics services issue grouped SQL (`GROUP BY` on a derived bucket,
 * `SUM(CASE ...)`, `NOW()` comparisons, LEFT JOINs) that Tests\FakeDatabase's
 * small WHERE matcher deliberately does not emulate. Rather than grow that
 * matcher into a SQL engine, this double does two useful things:
 *
 *  1. returns canned result sets, so the PHP post-processing the services are
 *     responsible for (zero-fill, canonical ordering, status bucketing) is
 *     genuinely exercised; and
 *  2. records every statement, so tests can assert on query composition — the
 *     product-filter join, the derived-status expression, `pl.active = 1`.
 *
 * Results are matched by the first rule whose needle appears in the SQL.
 */
final class AggregateDatabaseStub implements DatabaseInterface
{
    /** @var list<array{sql: string, params: array}> */
    public array $queries = [];

    /** @var list<array{needle: string, rows: array}> */
    private array $script = [];

    /** @var list<array{needle: string, rows: array}> consumed on first match */
    private array $once = [];

    private ?array $single = null;

    public function on(string $needle, array $rows): self
    {
        $this->script[] = ['needle' => $needle, 'rows' => $rows];

        return $this;
    }

    /**
     * Like {@see on()}, but expires after its first match. For current-vs-prior
     * query pairs whose SQL is textually identical (DateRange only changes the
     * bound params): script the current window as `onOnce`, the prior as `on`.
     */
    public function onOnce(string $needle, array $rows): self
    {
        $this->once[] = ['needle' => $needle, 'rows' => $rows];

        return $this;
    }

    public function returnsOne(?array $row): self
    {
        $this->single = $row;

        return $this;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $this->queries[] = ['sql' => $sql, 'params' => $params];

        if ($this->single !== null) {
            return $this->single;
        }

        return $this->match($sql)[0] ?? null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $this->queries[] = ['sql' => $sql, 'params' => $params];

        return $this->match($sql);
    }

    public function insert(string $table, array $data): int
    {
        return 0;
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        return 0;
    }

    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $this->queries[] = ['sql' => "COUNT FROM {$table} WHERE {$where}", 'params' => $params];

        return 0;
    }

    /** All recorded SQL, concatenated — convenient for `assertStringContainsString`. */
    public function sql(): string
    {
        return implode("\n", array_column($this->queries, 'sql'));
    }

    /** Params bound by the most recent query. */
    public function params(): array
    {
        $last = end($this->queries);

        return $last === false ? [] : ($last['params'] ?? []);
    }

    public function reset(): void
    {
        $this->queries = [];
        $this->once = [];
        $this->single = null;
    }

    private function match(string $sql): array
    {
        foreach ($this->once as $i => $rule) {
            if (str_contains($sql, $rule['needle'])) {
                array_splice($this->once, $i, 1);

                return $rule['rows'];
            }
        }

        foreach ($this->script as $rule) {
            if (str_contains($sql, $rule['needle'])) {
                return $rule['rows'];
            }
        }

        return [];
    }
}