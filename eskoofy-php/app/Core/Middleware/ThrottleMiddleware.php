<?php
declare(strict_types=1);

namespace App\Core\Middleware;

class ThrottleMiddleware
{
    private int $maxAttempts;
    private int $decayMinutes;

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
    }

    public function handle(): void
    {
        $key = 'throttle:' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $session = \App\Core\Session::getInstance();
        $attempts = $session->get($key, ['count' => 0, 'first_at' => 0]);

        if (time() - ($attempts['first_at'] ?? 0) > $this->decayMinutes * 60) {
            $attempts = ['count' => 0, 'first_at' => time()];
        }

        if ($attempts['count'] >= $this->maxAttempts) {
            http_response_code(429);
            header('Retry-After: ' . ($this->decayMinutes * 60));
            echo json_encode(['error' => 'Too many requests. Please try again later.']);
            exit;
        }

        $attempts['count']++;
        if ($attempts['count'] === 1) {
            $attempts['first_at'] = time();
        }
        $session->set($key, $attempts);
    }
}
