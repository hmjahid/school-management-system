<?php
declare(strict_types=1);

namespace App\Core\Middleware;

/**
 * Login attempt limiter — parity with the Laravel app's login rate limiter.
 *
 * Keyed on ip + submitted identity rather than ip alone, so one attacker
 * hammering a single account cannot exhaust the allowance for every other
 * account behind the same NAT/proxy.
 *
 * Mounted with the parameterized middleware form: 'LoginThrottle:5,15'.
 *
 * State lives in App\Services\RateLimiter, NOT the session, because
 * Auth::attempt()/login() call Session::regenerate() which destroys session
 * data — a session-backed counter would reset on every successful login and
 * never throttle anything.
 */
class LoginThrottleMiddleware
{
    private int $maxAttempts;
    private int $decaySeconds;

    /**
     * @param string|int $maxAttempts  maximum attempts in the window.
     * @param string|int $decayMinutes window length in minutes.
     */
    public function __construct(string|int $maxAttempts = 5, string|int $decayMinutes = 15)
    {
        if (is_string($maxAttempts) && str_contains($maxAttempts, ',')) {
            [$max, $decay] = array_pad(explode(',', $maxAttempts, 2), 2, null);
            $maxAttempts = $max;
            $decayMinutes = $decay ?? $decayMinutes;
        }

        $this->maxAttempts = max(1, (int) $maxAttempts);
        $this->decaySeconds = max(1, (int) $decayMinutes) * 60;
    }

    /**
     * Bucket identity: ip + the identity being attempted.
     */
    public static function key(?string $identity = null): string
    {
        $identity = $identity ?? (string) ($_POST['email'] ?? $_POST['esk_login'] ?? '');
        $ip = (new \App\Core\Request())->ip();

        return 'login:' . $ip . '|' . strtolower(trim($identity));
    }

    /**
     * Reset the bucket for a successful authentication, so a legitimate user
     * who fumbled their password is never left locked out.
     */
    public static function clearFor(string $identity): void
    {
        \App\Services\RateLimiter::clear(self::key($identity));
    }

    public function handle(): void
    {
        $key = self::key();

        if (\App\Services\RateLimiter::tooManyAttempts($key, $this->maxAttempts, $this->decaySeconds)) {
            $this->abort();
        }

        \App\Services\RateLimiter::hit($key, $this->decaySeconds);
    }

    private function abort(): void
    {
        $retryAfter = \App\Services\RateLimiter::availableIn(self::key(), $this->decaySeconds);

        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('Cache-Control: no-store, max-age=0');

        $message = 'Too many login attempts. Please try again in '
            . max(1, (int) ceil($retryAfter / 60)) . ' minute(s).';

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (str_contains($accept, 'text/html')) {
            header('Content-Type: text/html; charset=UTF-8');
            \App\Core\Session::getInstance()->flash('error', $message);
            require __DIR__ . '/../../../views/errors/429.php';

            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message, 'data' => null]);
    }
}