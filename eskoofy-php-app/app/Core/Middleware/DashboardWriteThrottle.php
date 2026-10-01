<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Services\RateLimiter;

/**
 * Throttles dashboard write requests — parity with the Laravel app's
 * App\Http\Middleware\DashboardWriteThrottle (120 writes / 60s per user+ip).
 *
 * Reads are never throttled, so browsing a busy list page cannot trip the
 * limiter; only POST/PUT/PATCH/DELETE count.
 *
 * Mounted on the /dashboard route group in routes/web.php, after AuthMiddleware
 * so the bucket key can include the authenticated user id.
 */
class DashboardWriteThrottle
{
    public const LIMIT = 120;

    public const DECAY_SECONDS = 60;

    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Bucket identity — user + ip, matching the app's key.
     */
    public static function key(): string
    {
        $userId = \App\Core\Auth::id() ?? 'guest';

        // Request::ip() honours proxy headers, which is what the app keys on.
        $ip = (new \App\Core\Request())->ip();

        return 'dashboard_write:' . $userId . ':' . $ip;
    }

    /**
     * True when the current method should be counted.
     */
    public static function isWriteMethod(?string $method = null): bool
    {
        $method = $method ?? strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        return in_array($method, self::WRITE_METHODS, true);
    }

    public function handle(): void
    {
        if (!self::isWriteMethod()) {
            return;
        }

        $key = self::key();

        if (RateLimiter::tooManyAttempts($key, self::LIMIT, self::DECAY_SECONDS)) {
            $this->abort();
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);
    }

    /**
     * Emit the 429. JSON clients get the standard envelope; browsers get the
     * app's 429 error page (mirrors how Router::dispatch() renders views/errors/404.php).
     */
    private function abort(): void
    {
        $retryAfter = RateLimiter::availableIn(self::key(), self::DECAY_SECONDS);

        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('Cache-Control: no-store, max-age=0');

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (str_contains($accept, 'text/html')) {
            header('Content-Type: text/html; charset=UTF-8');
            require __DIR__ . '/../../../views/errors/429.php';

            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'data' => null,
        ]);
    }
}