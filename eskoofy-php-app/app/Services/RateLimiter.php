<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Fixed-window rate limiter — parity with the Laravel app's RateLimiter facade
 * and its DashboardWriteThrottle middleware (120 writes / 60s per user+ip).
 *
 * State lives in one small file per bucket under storage/framework/rate-limit.
 * That choice matters:
 *
 *  - NOT $_SESSION — Auth::attempt()/login() call Session::regenerate(), which
 *    destroys session data, so a session-backed counter resets on every login
 *    and is useless as a brute-force guard. Laravel's limiter survives that.
 *  - NOT a schema table — the raw-PHP product ships schema.sql with no
 *    migration runner, and a counter table would need DDL on every deploy.
 *
 * Writes are guarded with an exclusive flock and the window is anchored to the
 * file's first hit, so a bucket expires on its own with no sweeper process.
 */
class RateLimiter
{
    /** Injected store directory; null means the default storage path. */
    private static ?string $directory = null;

    /** @var array<string, array{hits:int,started:int}> in-process memo for the current request. */
    private static array $memo = [];

    /**
     * Point the limiter at a different directory (tests, CLI tooling).
     */
    public static function useDirectory(?string $directory): void
    {
        self::$directory = $directory;
        self::$memo = [];
    }

    public static function directory(): string
    {
        return self::$directory ?? storage_path('framework/rate-limit');
    }

    /**
     * Register an attempt against a bucket.
     *
     * With a $limit, returns the attempts remaining in this window (Laravel
     * parity); without one, returns the raw attempt count.
     */
    public static function hit(string $key, int $decaySeconds, ?int $limit = null): int
    {
        $hits = self::record($key, $decaySeconds);

        return $limit === null ? $hits : max(0, $limit - $hits);
    }

    /**
     * Attempts left without recording a new attempt.
     */
    public static function remaining(string $key, int $limit, int $decaySeconds): int
    {
        $state = self::read($key, $decaySeconds);

        if ($state === null) {
            return $limit;
        }

        return max(0, $limit - $state['hits']);
    }

    /**
     * Whether the bucket is already over its allowance.
     *
     * Split out from the middleware so tests can assert the decision without
     * the middleware calling exit().
     */
    public static function tooManyAttempts(string $key, int $limit, int $decaySeconds): bool
    {
        $state = self::read($key, $decaySeconds);

        if ($state === null) {
            return false;
        }

        return $state['hits'] > $limit;
    }

    /**
     * Seconds until the current window expires (0 when no window is open).
     */
    public static function availableIn(string $key, int $decaySeconds): int
    {
        $state = self::read($key, $decaySeconds);

        if ($state === null) {
            return 0;
        }

        return max(0, ($state['started'] + $decaySeconds) - time());
    }

    /**
     * Drop a bucket — called after a successful authentication.
     */
    public static function clear(string $key): void
    {
        unset(self::$memo[$key]);

        $path = self::path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Drop every bucket in the active directory.
     */
    public static function flush(): void
    {
        self::$memo = [];

        $dir = self::directory();
        if (!is_dir($dir)) {
            return;
        }

        foreach ((array) glob($dir . '/*.json') as $file) {
            if (is_string($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Record one attempt and return the new attempt count for this window.
     */
    private static function record(string $key, int $decaySeconds): int
    {
        $dir = self::directory();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            // Storage is unwritable — fail open rather than lock every user out.
            return 0;
        }

        $path = self::path($key);
        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return 0;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return 0;
            }

            $now = time();
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

            if (
                !is_array($state)
                || !isset($state['hits'], $state['started'])
                || $now - (int) $state['started'] >= $decaySeconds
            ) {
                $state = ['hits' => 0, 'started' => $now];
            }

            $state['hits'] = (int) $state['hits'] + 1;
            $state['started'] = (int) $state['started'];

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($state));
            fflush($handle);

            self::$memo[$key] = $state;

            return $state['hits'];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Read a bucket, treating an expired window as absent.
     *
     * @return array{hits:int,started:int}|null
     */
    private static function read(string $key, int $decaySeconds): ?array
    {
        if (isset(self::$memo[$key])) {
            $memo = self::$memo[$key];
            if ((time() - $memo['started']) < $decaySeconds) {
                return $memo;
            }

            return null;
        }

        $path = self::path($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        if (
            !is_array($state)
            || !isset($state['hits'], $state['started'])
            || (time() - (int) $state['started']) >= $decaySeconds
        ) {
            return null;
        }

        self::$memo[$key] = ['hits' => (int) $state['hits'], 'started' => (int) $state['started']];

        return self::$memo[$key];
    }

    private static function path(string $key): string
    {
        return self::directory() . '/' . hash('sha256', $key) . '.json';
    }
}