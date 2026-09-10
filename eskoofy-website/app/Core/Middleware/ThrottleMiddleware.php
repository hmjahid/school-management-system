<?php
declare(strict_types=1);

namespace App\Core\Middleware;

class ThrottleMiddleware
{
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
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
