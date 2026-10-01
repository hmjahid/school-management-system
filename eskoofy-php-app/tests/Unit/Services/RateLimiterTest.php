<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RateLimiter;
use Tests\TestCase;

/**
 * RateLimiter is the shared fixed-window counter behind DashboardWriteThrottle,
 * ThrottleMiddleware and LoginThrottleMiddleware.
 *
 * The critical property is that state must NOT live in $_SESSION:
 * Auth::attempt()/login() call Session::regenerate(), which destroys session
 * data, so a session-backed counter resets on every login and cannot throttle.
 */
class RateLimiterTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/esk-rate-' . bin2hex(random_bytes(6));
        RateLimiter::useDirectory($this->dir);
    }

    protected function tearDown(): void
    {
        RateLimiter::flush();
        RateLimiter::useDirectory(null);
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function rewind(string $key, int $seconds): void
    {
        $path = $this->dir . '/' . hash('sha256', $key) . '.json';
        $state = json_decode((string) file_get_contents($path), true);
        $state['started'] -= $seconds;
        file_put_contents($path, (string) json_encode($state));
        RateLimiter::useDirectory($this->dir); // drop the in-process memo
    }

    public function test_unknown_bucket_is_never_blocked(): void
    {
        $this->assertFalse(RateLimiter::tooManyAttempts('a', 5, 60));
        $this->assertSame(5, RateLimiter::remaining('a', 5, 60));
        $this->assertSame(0, RateLimiter::availableIn('a', 60));
    }

    public function test_attempts_accumulate_until_the_limit_is_exceeded(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->assertSame(5 - $i, RateLimiter::hit('k', 60, 5), "attempt {$i}");
            $this->assertFalse(RateLimiter::tooManyAttempts('k', 5, 60), "attempt {$i} still allowed");
        }

        RateLimiter::hit('k', 60, 5); // 6th
        $this->assertTrue(RateLimiter::tooManyAttempts('k', 5, 60));
        $this->assertSame(0, RateLimiter::remaining('k', 5, 60));
    }

    public function test_remaining_does_not_consume_an_attempt(): void
    {
        RateLimiter::hit('k', 60, 5);

        $this->assertSame(4, RateLimiter::remaining('k', 5, 60));
        $this->assertSame(4, RateLimiter::remaining('k', 5, 60), 'reads must not consume attempts');
        $this->assertSame(3, RateLimiter::hit('k', 60, 5), 'the next hit consumes the 2nd attempt');
    }

    public function test_window_expiry_restores_the_full_allowance(): void
    {
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit('k', 60, 5);
        }
        $this->assertTrue(RateLimiter::tooManyAttempts('k', 5, 60));

        $this->rewind('k', 61);

        $this->assertFalse(RateLimiter::tooManyAttempts('k', 5, 60));
        $this->assertSame(5, RateLimiter::remaining('k', 5, 60));
    }

    public function test_available_in_counts_down_to_the_window_edge(): void
    {
        RateLimiter::hit('k', 60);
        $this->assertGreaterThan(0, RateLimiter::availableIn('k', 60));
        $this->assertLessThanOrEqual(60, RateLimiter::availableIn('k', 60));
    }

    public function test_clear_resets_a_blocked_bucket(): void
    {
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit('k', 60, 5);
        }
        $this->assertTrue(RateLimiter::tooManyAttempts('k', 5, 60));

        RateLimiter::clear('k');

        $this->assertFalse(RateLimiter::tooManyAttempts('k', 5, 60));
    }

    public function test_buckets_are_isolated_from_each_other(): void
    {
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit('user:1:1.1.1.1', 60, 5);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts('user:1:1.1.1.1', 5, 60));
        $this->assertFalse(RateLimiter::tooManyAttempts('user:2:1.1.1.1', 5, 60));
        $this->assertFalse(RateLimiter::tooManyAttempts('user:1:9.9.9.9', 5, 60));
    }

    public function test_survives_session_regeneration(): void
    {
        // The whole reason this is not a session counter: Auth::login() wipes
        // session data, which used to reset the throttle.
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit('k', 60, 5);
        }

        $_SESSION = [];
        session_regenerate_id(true);

        $this->assertTrue(
            RateLimiter::tooManyAttempts('k', 5, 60),
            'counter must outlive the session it was recorded in'
        );
    }

    public function test_state_is_persisted_on_disk(): void
    {
        RateLimiter::hit('k', 60, 5);

        $files = glob($this->dir . '/*.json') ?: [];
        $this->assertCount(1, $files, 'one file per bucket');

        $state = json_decode((string) file_get_contents($files[0]), true);
        $this->assertSame(1, $state['hits']);
        $this->assertIsInt($state['started']);
    }

    public function test_corrupt_state_fails_open(): void
    {
        @mkdir($this->dir, 0775, true);
        file_put_contents($this->dir . '/' . hash('sha256', 'k') . '.json', 'not json');

        $this->assertFalse(RateLimiter::tooManyAttempts('k', 5, 60), 'never blocks on unreadable state');
        $this->assertSame(1, RateLimiter::hit('k', 60), 'recovers into a fresh window');
    }
}