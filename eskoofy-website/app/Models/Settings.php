<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Key/value site settings, editable from Admin → Settings.
 * Values fall back to sane defaults until the site owner saves them.
 */
class Settings
{
    /** @var array<string, mixed> */
    private static array $defaults = [
        'site.name'              => 'Eskoofy',
        'site.tagline'           => 'School management software & WordPress theme',
        'site.contact_email'     => 'support@eskoofy.com',
        'site.currency_label'    => 'USD',
        'appearance.brand_color' => '#2563eb',
        'appearance.dark_default' => '0',
        'email.driver'           => 'sendmail',
        'email.host'             => '',
        'email.port'             => '587',
        'email.username'         => '',
        'email.password'         => '',
        'email.encryption'       => 'tls',
        'email.from_address'     => '',
        'email.from_name'        => 'Eskoofy',
        'services.deploy_app'    => '250',
        'services.deploy_php_theme' => '150',
        'services.care_monthly'  => '29',
    ];

    /** @var array<string, string>|null */
    private static ?array $loaded = null;

    /** @return array<string, string> key/value pairs */
    public static function all(): array
    {
        return self::load();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $store = self::load();

        if (array_key_exists($key, $store)) {
            return $store[$key] ?? $default;
        }

        return $default ?? (self::$defaults[$key] ?? null);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function set(string $key, mixed $value): void
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $value = $value === null ? null : (string) $value;

        $existing = $db->fetch("SELECT id FROM settings WHERE `key` = ?", [$key]);
        if ($existing) {
            $db->update('settings', ['value' => $value, 'updated_at' => $now], 'id = ?', [$existing['id']]);
        } else {
            $db->insert('settings', [
                'key'        => $key,
                'value'      => $value,
                'group'      => explode('.', $key, 2)[0],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        self::$loaded = null;
    }

    /** @param array<string, mixed> $pairs */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::set($key, $value);
        }
    }

    public static function mailFromAddress(): string
    {
        $address = trim((string) self::get('email.from_address', ''));

        return $address !== '' ? $address : (string) self::get('site.contact_email', '');
    }

    public static function mailFromName(): string
    {
        return trim((string) self::get('email.from_name', 'Eskoofy')) ?: 'Eskoofy';
    }

    /** @return array<string, string> */
    private static function load(): array
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }

        $pairs = [...self::$defaults];
        try {
            $rows = Database::getInstance()->fetchAll("SELECT `key`, `value` FROM settings");
            foreach ($rows as $row) {
                $pairs[(string) $row['key']] = (string) $row['value'];
            }
        } catch (\Throwable) {
            // DB may not be initialised yet (e.g. CLI/install); defaults apply.
        }

        self::$loaded = $pairs;

        return $pairs;
    }
}