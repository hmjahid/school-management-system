<?php
declare(strict_types=1);

namespace App\Core\Middleware;

class ThrottleMiddleware
{
    private int $maxAttempts;
    private int $decayMinutes;
    private int $decaySeconds;

    /**
     * @param string|int $maxAttempts maximum attempts in the decay window.
     * @param int        $decayMinutes window length in minutes.
     */
    public function __construct(string|int $maxAttempts = 60, int $decayMinutes = 1)
    {
        // Parameterized middleware name form: "Throttle:12,1".
        if (is_string($maxAttempts) && str_contains($maxAttempts, ',')) {
            [$max, $decay] = array_pad(explode(',', $maxAttempts, 2), 2, null);
            $maxAttempts = (int) trim((string) $max);
            $decayMinutes = (int) trim((string) ($decay ?? $decayMinutes));
        } elseif (is_string($maxAttempts)) {
            $maxAttempts = (int) trim($maxAttempts);
        }

        $this->maxAttempts = max(1, (int) $maxAttempts);
        $this->decayMinutes = max(1, $decayMinutes);
        $this->decaySeconds = $this->decayMinutes * 60;
    }

    /**
     * Bucket identity: ip + user when signed in.
     *
     * The ip comes from Request::ip() so the key matches the app's rate
     * limiting, which honours proxy headers.
     */
    public static function key(): string
    {
        $ip = (new \App\Core\Request())->ip();
        $userId = \App\Core\Auth::id();

        return 'throttle:' . $ip . ($userId !== null ? ':' . $userId : '');
    }

    public function handle(): void
    {
        $key = self::key();

        if (\App\Services\RateLimiter::tooManyAttempts($key, $this->maxAttempts, $this->decaySeconds)) {
            $this->abort();
        }

        \App\Services\RateLimiter::hit($key, $this->decaySeconds);
    }

    /**
     * Emit the 429 using the app's {success,message,data} envelope for API
     * callers, or the HTML error page for browsers. The previous
     * {"error": "..."} shape broke the envelope contract.
     */
    private function abort(): void
    {
        http_response_code(429);
        header('Retry-After: ' . $this->decaySeconds);
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
