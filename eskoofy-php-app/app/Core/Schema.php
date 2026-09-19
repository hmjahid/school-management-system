<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal Schema facade replacement (Illuminate\Support\Facades\Schema) so
 * Blade views that guard on table existence keep working.
 */
class Schema
{
    /** @var array<string,bool> */
    protected static array $tableCache = [];

    public static function hasTable(string $table): bool
    {
        if (array_key_exists($table, self::$tableCache)) {
            return self::$tableCache[$table];
        }
        try {
            return self::$tableCache[$table] = Database::getInstance()->hasTable($table);
        } catch (\Throwable) {
            return self::$tableCache[$table] = false;
        }
    }

    public static function hasColumn(string $table, string $column): bool
    {
        try {
            $db = Database::getInstance();
            $row = $db->fetch("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }
}
