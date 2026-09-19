<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Router;
use App\Services\VisitorLogger;
use Tests\TestCase;

class VisitorLogTest extends TestCase
{
    public function test_public_pages_are_trackable(): void
    {
        $this->assertTrue(VisitorLogger::isTrackable('/'));
        $this->assertTrue(VisitorLogger::isTrackable('/pricing'));
        $this->assertTrue(VisitorLogger::isTrackable('/blog/how-to-choose'));
    }

    public function test_private_and_asset_paths_are_not_trackable(): void
    {
        foreach ([
            '/admin', '/admin/visitors', '/account', '/account/licenses',
            '/api/v1/ping', '/webhooks/stripe', '/login', '/register',
            '/checkout', '/js/site.js', '/css/app.css', '/favicon.ico',
            '/icons/apple-touch-icon.png', '/manifest.json',
        ] as $path) {
            $this->assertFalse(VisitorLogger::isTrackable($path), "should skip {$path}");
        }
    }

    public function test_non_get_requests_are_skipped(): void
    {
        $this->assertFalse(VisitorLogger::shouldRecord('POST', '/contact'));
        $this->assertTrue(VisitorLogger::shouldRecord('GET', '/pricing'));
    }

    public function test_dnt_header_disables_recording(): void
    {
        $_SERVER['HTTP_DNT'] = '1';
        $this->assertFalse(VisitorLogger::shouldRecord('GET', '/pricing'));
        unset($_SERVER['HTTP_DNT']);
    }

    public function test_bot_user_agents_are_detected(): void
    {
        $this->assertTrue(VisitorLogger::isBot('Googlebot/2.1 (+http://www.google.com/bot.html)'));
        $this->assertTrue(VisitorLogger::isBot('curl/8.4.0'));
        $this->assertFalse(VisitorLogger::isBot('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36'));
        $this->assertTrue(VisitorLogger::isBot(''));
    }

    public function test_visitor_route_and_view_registered(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $paths = array_map(fn ($r) => $r['path'], $router->getRoutes());
        $this->assertContains('/admin/visitors', $paths);
        $this->assertFileExists(dirname(__DIR__, 3) . '/views/admin/visitors.php');
    }

    public function test_schema_defines_visitors_table(): void
    {
        $schema = (string) file_get_contents(dirname(__DIR__, 3) . '/database/schema.sql');
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `visitors`', $schema);
        $this->assertStringContainsString('`visited_at`', $schema);
    }

    public function test_support_settings_defaults_exist(): void
    {
        $en = (string) file_get_contents(dirname(__DIR__, 3) . '/lang/en.php');
        foreach (['support.title', 'support.launcher', 'support.cta', 'contact.email_support', 'footer.solutions'] as $key) {
            $this->assertStringContainsString("'{$key}'", $en, "missing key: {$key}");
        }
    }
}
