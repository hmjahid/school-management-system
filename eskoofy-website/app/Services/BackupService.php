<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\DatabaseInterface;

/**
 * Self-contained backup generator: dumps tables as portable SQL and, for a
 * full-site backup, bundles the SQL with the public assets in a ZIP archive.
 * No mysqldump required — works on shared hosting.
 */
class BackupService
{
    public const TYPE_FULL    = 'full';
    public const TYPE_LICENSES = 'licenses';
    public const TYPE_USERS   = 'users';

    /** @var array<string, list<string>> */
    private const TABLE_SETS = [
        self::TYPE_LICENSES => ['licenses', 'license_activations', 'subscriptions', 'plans', 'payments'],
        self::TYPE_USERS    => ['customers'],
    ];

    private DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public static function storageDir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /** @return list<string> */
    public static function allTables(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll('SHOW TABLES');
        $key = array_keys($rows[0] ?? ['Tables_in_' => '']);
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = (string) array_values($row)[0];
        }

        return $tables;
    }

    /** @param list<string> $tables */
    public function dumpSql(array $tables, string $label = ''): string
    {
        $pdo = $this->db->getConnection();
        $out = "-- Eskoofy backup" . ($label !== '' ? " ({$label})" : '')
            . "\n-- Generated: " . date('Y-m-d H:i:s')
            . "\nSET FOREIGN_KEY_CHECKS=0;\n";

        foreach ($tables as $table) {
            if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                continue;
            }
            $create = $this->db->fetch("SHOW CREATE TABLE `{$table}`");
            if (!$create) {
                continue;
            }
            $out .= "\n\n" . ($create['Create Table'] ?? '') . ";\n";
            $out .= "TRUNCATE TABLE `{$table}`;\n";

            $rows = $this->db->fetchAll("SELECT * FROM `{$table}`");
            foreach ($rows as $row) {
                $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($row)));
                $vals = implode(', ', array_map(
                    fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row)
                ));
                $out .= "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals});\n";
            }
        }

        $out .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        return $out;
    }

    /**
     * Create a backup file.
     *
     * @return array{file: string, size: int, type: string, full: bool}
     */
    public function create(string $type): array
    {
        $type = in_array($type, [self::TYPE_FULL, self::TYPE_LICENSES, self::TYPE_USERS], true)
            ? $type
            : self::TYPE_FULL;

        $stamp = date('Ymd-His');
        $tables = $type === self::TYPE_FULL ? self::allTables() : self::TABLE_SETS[$type];
        $sql = $this->dumpSql($tables, $type);

        $dir = self::storageDir();

        if ($type === self::TYPE_FULL && class_exists(\ZipArchive::class)) {
            $file = "backup-{$type}-{$stamp}.zip";
            $path = $dir . '/' . $file;

            $zip = new \ZipArchive();
            if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $zip->addFromString('database/site.sql', $sql);
                $this->addPublicAssets($zip);
                $zip->close();

                return ['file' => $file, 'size' => (int) @filesize($path), 'type' => $type, 'full' => true];
            }
        }

        $file = "backup-{$type}-{$stamp}.sql";
        $path = $dir . '/' . $file;
        file_put_contents($path, $sql);

        return ['file' => $file, 'size' => (int) @filesize($path), 'type' => $type, 'full' => false];
    }

    /** @return list<array{file: string, type: string, size: int, created: int, full: bool}> */
    public function list(): array
    {
        $dir = self::storageDir();
        $items = [];
        foreach ((array) glob($dir . '/backup-*.{sql,zip}', GLOB_BRACE) as $path) {
            $file = basename((string) $path);
            if (preg_match('/^backup-(full|licenses|users)-/', $file, $m)) {
                $items[] = [
                    'file'    => $file,
                    'type'    => $m[1],
                    'size'    => (int) @filesize($path),
                    'created' => (int) @filemtime($path),
                    'full'    => str_ends_with($file, '.zip'),
                ];
            }
        }
        usort($items, fn($a, $b) => $b['created'] <=> $a['created']);

        return $items;
    }

    public function download(string $file): ?string
    {
        $file = basename($file);
        $path = self::storageDir() . '/' . $file;
        if (!preg_match('/^backup-(full|licenses|users)-.*\.(sql|zip)$/', $file) || !is_file($path)) {
            return null;
        }

        return $path;
    }

    public function delete(string $file): bool
    {
        $path = $this->download($file);
        if ($path === null) {
            return false;
        }

        return @unlink($path);
    }

    private function addPublicAssets(\ZipArchive $zip, string $dir = '', string $prefix = ''): void
    {
        $base = dirname(__DIR__, 2) . '/public';
        $path = $base . ($dir !== '' ? '/' . $dir : '');

        foreach ((array) glob($path . '/*') as $item) {
            $name = basename((string) $item);
            if ($name === 'storage' && $dir === '') {
                continue;
            }
            $rel = $prefix . $name;
            if (is_file($item)) {
                $zip->addFile((string) $item, 'public/' . $rel);
            } elseif (is_dir($item)) {
                $this->addPublicAssets($zip, $dir . '/' . $name, $rel . '/');
            }
        }
    }
}