<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DatabaseInterface;
use RuntimeException;

/**
 * Portable backup format shared across Eskoofy variants — raw-PHP port.
 *
 * The zip layout is:
 *   MANIFEST.json            {format,version,variant,createdAt,engine,tableCount,
 *                             eskoofyVariant,sensitiveColumns}
 *   database/tables.json     {"tables":[{"table","columns","rows"}, ...]}  (rows are arrays aligned to columns)
 *   storage/app/public/...   user uploads
 *
 * Values are engine-neutral: dates become "Y-m-d H:i:s", booleans 1/0, decimals stay strings.
 * Mirrors eskoofy-laravel-app/app/Services/PortableBackupService.php so a backup created here
 * restores in the app and vice-versa (see docs/design/DATA-PORTABILITY.md).
 */
class PortableBackupService
{
    public const FORMAT = 'eskoofy-portable-backup';

    public const VERSION = 1;

    public const MANIFEST_FILE = 'MANIFEST.json';

    public const TABLES_FILE = 'database/tables.json';

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * @return array{manifest: array<string, mixed>, tables: array<string, mixed>}
     */
    public function dump(string $variant = 'raw-php'): array
    {
        $tables = $this->allTables();
        sort($tables);

        $dumpTables = [];
        foreach ($tables as $table) {
            $columns = [];
            $rows = [];
            foreach ($this->db->fetchAll("SELECT * FROM `{$table}`") as $record) {
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
                'createdAt' => date('Y-m-d H:i:s'),
                'engine' => 'mysql',
                'tableCount' => count($dumpTables),
                'eskoofyVariant' => (string) (config('eskoolfy.variant') ?? 'bd'),
                'sensitiveColumns' => [
                    'payment_gateways' => ['api_secret', 'api_key', 'client_secret', 'refresh_token'],
                    'website_settings' => ['bkash_api_secret', 'bkash_api_key', 'mail_password', 'twilio_auth_token'],
                ],
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
     * @param  array<int, array{table: string, columns: array<int, string>, rows: array<int, array<int, mixed>>}>  $tablesData
     */
    public function restoreTables(array $tablesData): void
    {
        $this->setForeignKeys(false);
        try {
            $this->db->beginTransaction();
            foreach ($tablesData as $tableData) {
                $table = $tableData['table'];
                if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
                    throw new RuntimeException("Unsafe table name: {$table}");
                }
                $columns = $tableData['columns'] ?? [];
                $rows = $tableData['rows'] ?? [];

                $this->db->query("DELETE FROM `{$table}`");

                foreach (array_chunk($rows, 200) as $chunk) {
                    foreach ($chunk as $row) {
                        $values = array_pad(array_values($row), count($columns), null);
                        $record = [];
                        foreach ($columns as $i => $column) {
                            $record[$column] = $values[$i] ?? null;
                        }
                        $this->db->insert($table, $record);
                    }
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        } finally {
            $this->setForeignKeys(true);
        }
    }

    /**
     * Build the portable zip in memory: MANIFEST.json + database/tables.json +
     * storage/app/public/**. Returns the zip bytes.
     */
    public function zip(string $variant = 'raw-php'): string
    {
        $dump = $this->dump($variant);
        $zip = new \ZipArchive();
        $path = tempnam(sys_get_temp_dir(), 'esk-portable-') . '.zip';
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open portable backup zip.');
        }

        $zip->addFromString(self::MANIFEST_FILE, json_encode($dump['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString(self::TABLES_FILE, json_encode($dump['tables']));

        foreach ($this->storageEntries() as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();
        $bytes = (string) file_get_contents($path);
        @unlink($path);

        return $bytes;
    }

    /**
     * @return array<string, string>  zip entry name => filesystem path
     */
    private function storageEntries(): array
    {
        $root = storage_path('app/public');
        $entries = [];
        if (! is_dir($root)) {
            return $entries;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $rel = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');
            $entries['storage/app/public/' . $rel] = $file->getPathname();
        }

        return $entries;
    }

    /**
     * Re-set variant-owned configuration rows to the receiving variant's profile
     * after a CROSS-variant restore.
     *
     * @return array<int, string> human-readable notes for the operator
     */
    public function reconcileVariant(?string $sourceVariant): array
    {
        $target = (string) (config('eskoolfy.variant') ?? 'bd');
        if ($sourceVariant === null || $sourceVariant === $target) {
            return [];
        }

        $restore = (array) (config('eskoolfy.restore') ?? []);
        $gateways = (array) ($restore['gateways'][$target] ?? []);
        $settings = (array) ($restore['settings'] ?? []);
        $currency = (string) ($settings['currency'][$target] ?? 'BDT');
        $notes = [];

        if ($this->db->hasTable('payment_gateways')) {
            foreach ($this->db->fetchAll('SELECT code FROM payment_gateways') as $row) {
                $code = (string) ($row['code'] ?? '');
                if (! in_array($code, $gateways, true)) {
                    $this->db->update('payment_gateways', ['is_active' => 0], 'code = ?', [$code]);
                    $notes[] = "Gateway '{$code}' deactivated (not part of the {$target} profile).";
                }
            }
            foreach ($gateways as $code) {
                $updated = $this->db->update(
                    'payment_gateways',
                    ['is_active' => 1, 'currency' => $currency],
                    'code = ?',
                    [$code]
                );
                $notes[] = $updated > 0
                    ? "Gateway '{$code}' activated ({$currency})."
                    : "Gateway '{$code}' is missing after restore — add it in Settings › Payments.";
            }
        }

        if ($this->db->hasTable('website_settings')) {
            $assignments = [];
            foreach ([
                'currency' => $currency,
                'default_payment_method' => $settings['default_payment_method'][$target] ?? null,
                'default_locale' => $settings['default_locale'][$target] ?? null,
            ] as $column => $value) {
                if ($value !== null) {
                    $assignments[$column] = $value;
                }
            }
            if ($assignments !== []) {
                $this->db->update('website_settings', $assignments, '1=1');
                $notes[] = "website_settings reconciled to the {$target} profile ({$currency}).";
            }
        }

        if ($target === 'int' && ($restore['strip_bangla_for_int'] ?? true)) {
            foreach (['website_settings', 'admission_settings', 'website_contents'] as $table) {
                if (! $this->db->hasTable($table)) {
                    continue;
                }
                foreach ($this->tableColumns($table) as $column) {
                    $isBn = str_ends_with($column, '_bn');
                    $isPaymentNumber = $table === 'admission_settings' && $column === 'payment_number';
                    if (! $isBn && ! $isPaymentNumber) {
                        continue;
                    }
                    $this->db->update($table, [$column => null], '1=1');
                    $notes[] = "{$table}.{$column} cleared (Bengali content has no home in the int profile).";
                }
            }
        }

        return $notes;
    }

    /**
     * @return array<int, string>
     */
    private function allTables(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
        );

        return array_map(
            static fn (array $row): string => (string) ($row['name'] ?? array_values($row)[0] ?? ''),
            $rows
        );
    }

    /**
     * @return array<int, string>
     */
    private function tableColumns(string $table): array
    {
        if (! $this->db->hasTable($table)) {
            return [];
        }

        $rows = $this->db->fetchAll(
            'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table]
        );

        return array_map(
            static fn (array $row): string => (string) ($row['name'] ?? array_values($row)[0] ?? ''),
            $rows
        );
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return (int) $value;
        }

        return $value;
    }

    private function setForeignKeys(bool $enabled): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = ' . ($enabled ? 1 : 0));
    }
}