<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Portable backup format shared across Eskoofy variants (Laravel app / Node.js).
 *
 * The zip layout is:
 *   MANIFEST.json            {format:"eskoofy-portable-backup",version,variant,createdAt,engine,tableCount}
 *   database/tables.json     {"tables":[{"table","columns","rows"}, ...]}  (rows are arrays aligned to columns)
 *   storage/app/public/...   user uploads
 *
 * Values are engine-neutral: dates become "Y-m-d H:i:s", booleans 1/0, decimals stay strings.
 */
class PortableBackupService
{
    public const FORMAT = 'eskoofy-portable-backup';

    public const VERSION = 1;

    public const MANIFEST_FILE = 'MANIFEST.json';

    public const TABLES_FILE = 'database/tables.json';

    /**
     * @param  string  $variant  'laravel' (or 'node' when imported). Not validated on restore.
     * @return array{manifest: array<string, mixed>, tables: array<string, mixed>}
     */
    public function dump(string $variant = 'laravel'): array
    {
        $tables = $this->allTables();
        sort($tables);

        $dumpTables = [];
        foreach ($tables as $table) {
            $columns = [];
            $rows = [];
            foreach (DB::table($table)->get() as $record) {
                $row = (array) $record;
                $columns = array_keys($row);
                $rows[] = array_map(fn ($value) => $this->serializeValue($value), array_values($row));
            }
            $dumpTables[] = [
                'table' => $table,
                'columns' => $columns,
                'rows' => $rows,
            ];
        }

        return [
            'manifest' => [
                'format' => self::FORMAT,
                'version' => self::VERSION,
                'variant' => $variant,
                'createdAt' => now()->format('Y-m-d H:i:s'),
                'engine' => DB::connection()->getDriverName(),
                'tableCount' => count($dumpTables),
            ],
            'tables' => ['tables' => $dumpTables],
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function isValidManifest(array $manifest = []): bool
    {
        return ($manifest['format'] ?? null) === self::FORMAT
            && is_int($manifest['version'] ?? null)
            && $manifest['version'] <= self::VERSION;
    }

    /**
     * Parse database/tables.json back into the {table, columns, rows} shape.
     *
     * @return array<int, array{table: string, columns: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    public function parseTables(string $json): array
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! isset($data['tables']) || ! is_array($data['tables'])) {
            throw new RuntimeException('Portable backup is missing database/tables.json.');
        }

        return $data['tables'];
    }

    /**
     * Delete every table then re-insert its rows (chunked) with FK checks off.
     *
     * @param  array<int, array{table: string, columns: array<int, string>, rows: array<int, array<int, mixed>>}>  $tablesData
     */
    public function restoreTables(array $tablesData): void
    {
        $this->setForeignKeys(false);
        try {
            DB::beginTransaction();
            foreach ($tablesData as $tableData) {
                $table = $tableData['table'];
                if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
                    throw new RuntimeException("Unsafe table name: {$table}");
                }
                $columns = $tableData['columns'] ?? [];
                $rows = $tableData['rows'] ?? [];

                DB::statement("DELETE FROM {$table}");

                foreach (array_chunk($rows, 200) as $chunk) {
                    $records = [];
                    foreach ($chunk as $row) {
                        $records[] = array_combine($columns, array_pad(array_values($row), count($columns), null));
                    }
                    if ($records !== []) {
                        DB::table($table)->insert($records);
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            $this->setForeignKeys(true);
        }
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    private function allTables(): array
    {
        $driver = DB::connection()->getDriverName();
        $rows = match ($driver) {
            'sqlite' => DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"),
            'mysql', 'mariadb' => DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'),
            'pgsql' => DB::select("SELECT tablename AS name FROM pg_tables WHERE schemaname = 'public'"),
            default => [],
        };

        return array_map(fn ($row) => (string) array_values((array) $row)[0], $rows);
    }

    private function setForeignKeys(bool $enabled): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // foreign_keys is a no-op inside a transaction; defer_foreign_keys defers checks to the real commit.
            DB::statement($enabled ? 'PRAGMA defer_foreign_keys = OFF' : 'PRAGMA foreign_keys = OFF');
            DB::statement($enabled ? 'PRAGMA foreign_keys = ON' : 'PRAGMA defer_foreign_keys = ON');

            return;
        }

        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS = '.($enabled ? 1 : 0)),
            'pgsql' => DB::statement("SET session_referential_integrity = '".($enabled ? 'on' : 'off')."'"),
            default => null,
        };
    }
}
