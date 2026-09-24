<?php

namespace App\Services;

use App\Models\PaymentGateway;
use App\Models\WebsiteSetting;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Portable backup format shared across Eskoofy variants (Laravel app / Node.js).
 *
 * The zip layout is:
 *   MANIFEST.json            {format,version,variant,createdAt,engine,tableCount,
 *                             eskoofyVariant,cipherFingerprint,sensitiveColumns}
 *   database/tables.json     {"tables":[{"table","columns","rows"}, ...]}  (rows are arrays aligned to columns)
 *   storage/app/public/...   user uploads
 *
 * Values are engine-neutral: dates become "Y-m-d H:i:s", booleans 1/0, decimals stay strings.
 *
 * Cross-variant restores (see docs/design/DATA-PORTABILITY.md):
 *   - `eskoofyVariant` tags which bd/int profile produced the backup.
 *   - `cipherFingerprint` identifies the APP_KEY that wraps the encrypted columns
 *     (`sensitiveColumns`, derived from the models' `encrypted` casts). Values are kept
 *     only when source and target fingerprints match; otherwise the columns are cleared
 *     (a foreign/app-key or plaintext source is never decrypted blindly).
 *   - When the source variant differs from the receiving variant, `reconcileVariant()`
 *     re-sets the variant-owned configuration rows to the receiving profile's defaults
 *     (gateways, currency, default payment method, Bengali content), keeping business
 *     data verbatim.
 */
class PortableBackupService
{
    public const FORMAT = 'eskoofy-portable-backup';

    public const VERSION = 1;

    public const MANIFEST_FILE = 'MANIFEST.json';

    public const TABLES_FILE = 'database/tables.json';

    /**
     * Fingerprint of the encryption environment that wrapped `sensitiveColumns`.
     * The Node variant has no encryption layer and therefore reports null.
     */
    public function cipherFingerprint(): ?string
    {
        $key = config('app.key');

        return $key ? hash('sha256', $key) : null;
    }

    /**
     * Columns whose values are wrapped by the source variant's encryption
     * (the models' `encrypted` casts). Derived from the models so this cannot drift.
     *
     * @return array<string, array<int, string>>
     */
    public function sensitiveColumns(): array
    {
        $encrypted = static fn ($casts) => array_keys(array_filter($casts, static fn ($cast) => $cast === 'encrypted'));

        return [
            'payment_gateways' => $encrypted((new PaymentGateway)->getCasts()),
            'website_settings' => $encrypted((new WebsiteSetting)->getCasts()),
        ];
    }

    /**
     * @param  string  $variant  Engine label ('laravel'). Not validated on restore.
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
                'eskoofyVariant' => config('eskoolfy.variant', 'bd'),
                'cipherFingerprint' => $this->cipherFingerprint(),
                'sensitiveColumns' => $this->sensitiveColumns(),
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
     * Encrypted columns are cleared (set to null) after the insert when the source
     * backup's cipher fingerprint does not match this instance's key — the ciphertext
     * cannot be decrypted here, so a refused (null) value is safer than a crash later.
     *
     * @param  array<int, array{table: string, columns: array<int, string>, rows: array<int, array<int, mixed>>}>  $tablesData
     * @param  array<string, array<int, string>>|null  $sensitiveColumns
     * @return array<int, string> columns that were cleared because the keys differ
     */
    public function restoreTables(array $tablesData, ?array $sensitiveColumns = null, ?string $sourceFingerprint = null): array
    {
        $cleared = [];

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
            $cleared = $this->clearSensitiveColumns($tablesData, $sensitiveColumns, $sourceFingerprint);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            $this->setForeignKeys(true);
        }

        return $cleared;
    }

    /**
     * Re-set variant-owned configuration rows to the receiving variant's profile
     * after a CROSS-variant restore. Called from the restore surface only.
     *
     * @return array<int, string> human-readable notes for the operator
     */
    public function reconcileVariant(?string $sourceVariant): array
    {
        $target = config('eskoolfy.variant', 'bd');
        if ($sourceVariant === null || $sourceVariant === $target) {
            return [];
        }

        $restore = config('eskoolfy.restore', []);
        $gateways = $restore['gateways'][$target] ?? [];
        $settings = $restore['settings'] ?? [];
        $currency = $settings['currency'][$target] ?? 'BDT';
        $notes = [];

        // Gateways: activate the receiving profile's default set, deactivate the rest.
        foreach (DB::table('payment_gateways')->pluck('code') as $code) {
            if (! in_array($code, $gateways, true)) {
                DB::table('payment_gateways')->where('code', $code)->update(['is_active' => 0]);
                $notes[] = "Gateway '{$code}' deactivated (not part of the {$target} profile).";
            }
        }
        foreach ($gateways as $code) {
            $updated = DB::table('payment_gateways')->where('code', $code)->update([
                'is_active' => 1,
                'currency' => $currency,
            ]);
            $notes[] = $updated > 0
                ? "Gateway '{$code}' activated ({$currency})."
                : "Gateway '{$code}' is missing after restore — add it in Settings › Payments.";
        }

        // Website settings that drive per-variant runtime defaults.
        $set = array_filter([
            'currency' => $currency,
            'default_payment_method' => $settings['default_payment_method'][$target] ?? null,
            'default_locale' => $settings['default_locale'][$target] ?? null,
        ], static fn ($value) => $value !== null);

        foreach ($set as $column => $value) {
            if (! Schema::hasColumn('website_settings', $column)) {
                continue;
            }
            DB::table('website_settings')->update([$column => $value]);
            $notes[] = "website_settings.{$column} set to '{$value}'.";
        }

        // Bengali-only UI content has no home in the int profile.
        if (($target === 'int') && ($restore['strip_bangla_for_int'] ?? true)) {
            foreach (['website_settings', 'admission_settings', 'website_contents'] as $table) {
                foreach ($this->explodeTableColumns($table) as $column) {
                    if (! str_ends_with($column, '_bn') && ! ($table === 'admission_settings' && $column === 'payment_number')) {
                        continue;
                    }
                    DB::table($table)->update([$column => null]);
                    $notes[] = "{$table}.{$column} cleared (Bengali content has no home in the int profile).";
                }
            }
        }

        return $notes;
    }

    /**
     * Null every sensitive column whose source cipher fingerprint does not match.
     *
     * @param  array<int, array{table: string, columns: array<int, string>, rows: array<int, array<int, mixed>>}>  $tablesData
     * @param  array<string, array<int, string>>|null  $sensitiveColumns
     * @return array<int, string>
     */
    private function clearSensitiveColumns(array $tablesData, ?array $sensitiveColumns, ?string $sourceFingerprint): array
    {
        if (! $sensitiveColumns) {
            return [];
        }
        if ($sourceFingerprint !== null && $sourceFingerprint === $this->cipherFingerprint()) {
            return []; // same encryption environment — ciphertext remains valid
        }

        $cleared = [];
        foreach ($tablesData as $tableData) {
            $table = $tableData['table'];
            $columns = $tableData['columns'] ?? [];
            foreach (($sensitiveColumns[$table] ?? []) as $column) {
                if (! in_array($column, $columns, true)) {
                    continue;
                }
                DB::table($table)->update([$column => null]);
                $cleared[] = "{$table}.{$column}";
            }
        }

        return $cleared;
    }

    /**
     * @return array<int, string>
     */
    private function explodeTableColumns(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return array_values(array_map(
            static fn ($column) => $column['name'] ?? $column->getName(),
            Schema::getColumns($table)
        ));
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
