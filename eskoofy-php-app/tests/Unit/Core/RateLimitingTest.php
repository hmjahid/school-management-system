<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Middleware\DashboardWriteThrottle;
use App\Core\Middleware\LoginThrottleMiddleware;
use App\Core\Middleware\ThrottleMiddleware;
use App\Services\RateLimiter;
use Tests\TestCase;

/**
 * Wiring + behaviour guards for the three rate limiters.
 *
 * The middleware abort() methods `require` an HTML page on HTML requests, so
 * these tests assert on the shared predicate/key helpers and on REQUEST_METHOD
 * filtering rather than letting a middleware exit mid-run.
 */
class RateLimitingTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/esk-rl-' . bin2hex(random_bytes(6));
        RateLimiter::useDirectory($this->dir);
    }

    protected function tearDown(): void
    {
        RateLimiter::flush();
        RateLimiter::useDirectory(null);
        @rmdir($this->dir);

        parent::tearDown();
    }

    /* ------------------------------------------------------------------ */
    /* Dashboard write throttle                                             */
    /* ------------------------------------------------------------------ */

    public function test_dashboard_throttle_only_counts_write_methods(): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->assertTrue(DashboardWriteThrottle::isWriteMethod($method), $method);
        }

        foreach (['GET', 'HEAD', 'OPTIONS'] as $method) {
            $this->assertFalse(DashboardWriteThrottle::isWriteMethod($method), $method);
        }
    }

    public function test_dashboard_throttle_matches_app_limits(): void
    {
        $this->assertSame(120, DashboardWriteThrottle::LIMIT);
        $this->assertSame(60, DashboardWriteThrottle::DECAY_SECONDS);
    }

    public function test_dashboard_throttle_key_uses_user_and_ip(): void
    {
        $_SESSION['user_id'] = 42;
        $this->assertSame('dashboard_write:42:127.0.0.1', DashboardWriteThrottle::key());

        $_SESSION['user_id'] = 7;
        $this->assertSame('dashboard_write:7:127.0.0.1', DashboardWriteThrottle::key());
    }

    public function test_dashboard_throttle_key_falls_back_to_guest(): void
    {
        $this->assertSame('dashboard_write:guest:127.0.0.1', DashboardWriteThrottle::key());
    }

    public function test_dashboard_throttle_blocks_the_121st_write(): void
    {
        $_SESSION['user_id'] = 1;
        $key = DashboardWriteThrottle::key();

        for ($i = 0; $i < 120; $i++) {
            RateLimiter::hit($key, DashboardWriteThrottle::DECAY_SECONDS);
        }

        $this->assertFalse(RateLimiter::tooManyAttempts($key, DashboardWriteThrottle::LIMIT, DashboardWriteThrottle::DECAY_SECONDS));

        RateLimiter::hit($key, DashboardWriteThrottle::DECAY_SECONDS);

        $this->assertTrue(RateLimiter::tooManyAttempts($key, DashboardWriteThrottle::LIMIT, DashboardWriteThrottle::DECAY_SECONDS));
    }

    public function test_dashboard_throttle_reads_are_never_throttled(): void
    {
        $_SESSION['user_id'] = 1;
        $key = DashboardWriteThrottle::key();

        // Hammer the bucket with writes...
        for ($i = 0; $i < 200; $i++) {
            RateLimiter::hit($key, DashboardWriteThrottle::DECAY_SECONDS);
        }

        // ...and a GET still passes the method gate, so it is never counted or blocked.
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse(DashboardWriteThrottle::isWriteMethod());
    }

    public function test_dashboard_group_carries_the_throttle(): void
    {
        $routes = (string) file_get_contents(dirname(__DIR__, 3) . '/routes/web.php');

        $this->assertStringContainsString(
            "}, ['AuthMiddleware', 'DashboardWriteThrottle']);",
            $routes,
            'the /dashboard group must mount DashboardWriteThrottle after AuthMiddleware'
        );
    }

    /* ------------------------------------------------------------------ */
    /* Generic throttle                                                     */
    /* ------------------------------------------------------------------ */

    public function test_generic_throttle_parses_the_parameterized_middleware_name(): void
    {
        $middleware = new ThrottleMiddleware('12,1');

        $this->assertTrue(RateLimiter::tooManyAttempts(ThrottleMiddleware::key(), 12, 60) === false);
        $middleware->handle();
        $this->assertFalse(RateLimiter::tooManyAttempts(ThrottleMiddleware::key(), 12, 60));
    }

    public function test_generic_throttle_does_not_store_state_in_the_session(): void
    {
        $key = ThrottleMiddleware::key();

        RateLimiter::hit($key, 60);

        $this->assertArrayNotHasKey($key, $_SESSION, 'the old implementation wrote its counter into $_SESSION');
        $this->assertSame(4, RateLimiter::remaining($key, 5, 60), 'the counter was recorded in the limiter');
    }

    /* ------------------------------------------------------------------ */
    /* Login throttle                                                       */
    /* ------------------------------------------------------------------ */

    public function test_login_throttle_key_includes_ip_and_identity(): void
    {
        $this->assertSame('login:127.0.0.1|admin@school.com', LoginThrottleMiddleware::key('Admin@School.com'));
    }

    public function test_login_throttle_buckets_are_per_account(): void
    {
        $a = LoginThrottleMiddleware::key('victim@school.com');
        $b = LoginThrottleMiddleware::key('admin@school.com');

        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit($a, 900, 5);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts($a, 5, 900));
        $this->assertFalse(RateLimiter::tooManyAttempts($b, 5, 900), 'a second account on the same ip must not be locked out');
    }

    public function test_successful_login_clears_the_bucket(): void
    {
        $identity = 'admin@school.com';
        $key = LoginThrottleMiddleware::key($identity);

        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit($key, 900, 5);
        }
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5, 900));

        LoginThrottleMiddleware::clearFor($identity);

        $this->assertFalse(RateLimiter::tooManyAttempts($key, 5, 900));
    }

    public function test_login_routes_are_throttled(): void
    {
        $routes = (string) file_get_contents(dirname(__DIR__, 3) . '/routes/web.php');

        foreach (['/login', '/student/login', '/guardian/login'] as $path) {
            $this->assertMatchesRegularExpression(
                "/post\('" . preg_quote($path, '/') . "',[^;]*LoginThrottle/",
                $routes,
                "POST {$path} must be rate limited"
            );
        }
    }

    public function test_controllers_clear_the_login_bucket_on_success(): void
    {
        $auth = (string) file_get_contents(dirname(__DIR__, 3) . '/app/Controllers/AuthController.php');
        $portal = (string) file_get_contents(dirname(__DIR__, 3) . '/app/Controllers/Auth/StudentGuardianAuthController.php');

        $this->assertStringContainsString('LoginThrottleMiddleware::clearFor', $auth);
        $this->assertStringContainsString('LoginThrottleMiddleware::clearFor', $portal);
    }
}