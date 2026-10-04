<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Minimal file-backed cache for expensive read models (dashboard widgets,
 * analytics aggregates).
 *
 * The website has no Composer runtime and no cache server (shared hosting), so
 * this is deliberately just an atomic file write with a per-key TTL plus an
 * in-request memo layer. It is NOT a general-purpose cache — no tags, no
 * eviction strategy beyond TTL.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.2
 */
final class Cache
{
    /** TTLs in seconds, per widget. Kept here so they are auditable in one place. */
    public const TTL = [
        'kpi'           => 300,  // 5 min
        'revenue'       => 300,
        'matrix'        => 600,  // 10 min
        'fleet'         => 300,
        'traffic'       => 600,
        'licenses'      => 300,
        'gateways'      => 600,
        'activity'      => 120,
        'nav'           => 3600, // 1 hour — the nav is nearly static
        'palette'       => 300,
    ];

    public const DEFAULT_TTL = 300;

    /** @var array<string, mixed> in-request memoisation, keyed like the file cache */
    private static array $memo = [];

    private static ?string $dir = null;

    /** Test seam: point the cache at a scratch directory, or null to use the default. */
    public static function setDirectory(?string $dir): void
    {
        self::$dir = $dir;
        self::$memo = [];
    }

    /** Drop every in-request memoised value (used after a write invalidates a key). */
    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    public static function directory(): string
    {
        if (self::$dir !== null) {
            return self::$dir;
        }

        return dirname(__DIR__, 2) . '/storage/cache';
    }

    /**
     * Read a cached value, or null when absent/expired.
     *
     * @param string $group cache namespace, e.g. `kpi`
     * @param string $key   unique within the group
     */
    public static function get(string $group, string $key): mixed
    {
        $memoKey = $group . ':' . $key;

        if (array_key_exists($memoKey, self::$memo)) {
            return self::$memo[$memoKey];
        }

        $path = self::path($group, $key);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || !array_key_exists('v', $payload)) {
            return null;
        }

        $expiresAt = (int) ($payload['x'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < time()) {
            @unlink($path);

            return null;
        }

        return self::$memo[$memoKey] = $payload['v'];
    }

    public static function has(string $group, string $key): bool
    {
        return self::get($group, $key) !== null;
    }

    /**
     * Store a value with an explicit TTL.
     *
     * @param string $group
     * @param string $key
     * @param int    $ttl seconds; <= 0 disables caching for this write
     */
    public static function put(string $group, string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl ??= self::ttlFor($group);
        if ($ttl <= 0) {
            // "No caching" must mean no caching: bail out before the memo layer too,
            // otherwise a ttl of 0 would still be served for the rest of the request.
            return;
        }

        $memoKey = $group . ':' . $key;
        self::$memo[$memoKey] = $value;

        $dir = self::directory();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return; // Cache is an optimisation — never fail the request over it.
        }

        $payload = json_encode(['x' => time() + $ttl, 'v' => $value]);
        if ($payload === false) {
            return;
        }

        // Atomic write: a concurrent reader must never see a half-written file.
        $tmp = self::path($group, $key) . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, $payload, LOCK_EX) !== false) {
            @rename($tmp, self::path($group, $key));
        }
    }

    /**
     * Read-through: return the cached value, or compute + store it.
     *
     * @template T
     * @param string   $group
     * @param string   $key
     * @param callable():T $producer
     * @return T
     */
    public static function remember(string $group, string $key, callable $producer, ?int $ttl = null): mixed
    {
        $cached = self::get($group, $key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $producer();
        self::put($group, $key, $value, $ttl);

        return $value;
    }

    /** Invalidate one key, or a whole group when `$key` is null. */
    public static function forget(string $group, ?string $key = null): void
    {
        if ($key !== null) {
            $memoKey = $group . ':' . $key;
            unset(self::$memo[$memoKey]);
            @unlink(self::path($group, $key));

            return;
        }

        self::flushMemo();
        foreach (glob(self::directory() . '/' . self::sanitize($group) . '-*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    /** Remove every expired entry. Called opportunistically by the cron task. */
    public static function prune(): int
    {
        $removed = 0;
        foreach (glob(self::directory() . '/*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            $payload = $raw === false ? null : json_decode($raw, true);
            if (!is_array($payload) || (int) ($payload['x'] ?? 0) < time()) {
                @unlink($file);
                $removed++;
            }
        }

        return $removed;
    }

    public static function ttlFor(string $group): int
    {
        return self::TTL[$group] ?? self::DEFAULT_TTL;
    }

    /** Whether caching is currently usable (false in tests with no writable dir). */
    public static function isAvailable(): bool
    {
        $dir = self::directory();

        return is_dir($dir) ? is_writable($dir) : is_writable(dirname($dir));
    }

    private static function path(string $group, string $key): string
    {
        return self::directory() . '/' . self::sanitize($group) . '-' . sha1($key) . '.json';
    }

    private static function sanitize(string $value): string
    {
        return preg_replace('/[^a-z0-9_.-]/i', '_', $value) ?: 'cache';
    }
}