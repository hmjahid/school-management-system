<?php

declare(strict_types=1);

namespace Tests\Unit\Views;

use App\Core\Database;
use App\Models\Settings;
use Tests\Support\AggregateDatabaseStub;
use Tests\TestCase;

/**
 * Renders the admin app shell (`views/layouts/admin.php`) end to end.
 *
 * The shell is included by every `/admin/*` page but was previously executed by
 * no test, so an undefined variable or a missing design-system asset would only
 * surface in a browser. This renders it with warnings promoted to failures and
 * asserts the design system is actually wired in.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §6.1
 */
class AdminShellViewTest extends TestCase
{
    private AggregateDatabaseStub $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new AggregateDatabaseStub();
        $this->db->returnsOne(['total' => 0]);
        Database::setInstance($this->db);
        Settings::resetCache();

        $_SERVER['REQUEST_URI'] = '/admin/dashboard';
    }

    private function render(): string
    {
        $contentHtml = '<div id="esk-content-marker">page body</div>';
        $adminTitle = 'Dashboard';
        $admin = ['name' => 'Admin', 'email' => 'admin@eskoofy.com'];

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        ob_start();

        try {
            include dirname(__DIR__, 3) . '/views/layouts/admin.php';
            $html = (string) ob_get_contents();
        } catch (\Throwable $e) {
            ob_end_clean();
            restore_error_handler();

            self::fail('Admin shell raised ' . $e::class . ': ' . $e->getMessage());

            return '';
        }

        ob_end_clean();
        restore_error_handler();

        return $html;
    }

    public function testTheShellRendersAndInjectsThePageBody(): void
    {
        $html = $this->render();

        self::assertStringContainsString('id="esk-content-marker"', $html);
        self::assertGreaterThan(2000, strlen($html));
    }

    public function testTheDesignSystemAssetsAreWiredIn(): void
    {
        $html = $this->render();

        self::assertStringContainsString('/css/admin.css', $html);
        self::assertStringContainsString('/vendor/charts/chart.umd.min.js', $html);
        self::assertStringContainsString('/vendor/charts/apexcharts.min.js', $html);
        self::assertStringContainsString('/js/charts.js', $html);
        self::assertStringContainsString('/js/admin.js', $html);
    }

    public function testTheShellProvidesEveryHookAdminJsQueries(): void
    {
        $html = $this->render();

        foreach ([
            'data-admin-sidebar',
            'data-admin-backdrop',
            'data-admin-open',
            'data-admin-rail',
            'data-theme-set="dark"',
            'data-theme-set="light"',
            'data-theme-set="system"',
            'data-palette',
            'data-palette-input',
            'data-palette-list',
            'data-palette-open',
            'data-range-input',
        ] as $hook) {
            self::assertStringContainsString($hook, $html, "missing admin.js hook: {$hook}");
        }
    }

    public function testThePrePaintThemeScriptRunsBeforeTheBody(): void
    {
        $html = $this->render();

        $themePos = strpos($html, "setAttribute('data-theme'");
        $bodyPos = strpos($html, '<body');
        self::assertNotFalse($themePos);
        self::assertNotFalse($bodyPos);
        self::assertLessThan($bodyPos, $themePos, 'dark mode would flash the light theme');
    }

    public function testTheSidebarRendersEveryDestination(): void
    {
        $html = $this->render();

        foreach (['Dashboard', 'Licenses', 'Payments', 'Subscriptions', 'Customers', 'Settings'] as $label) {
            self::assertStringContainsString($label, $html);
        }
    }

    /** B8: no hardcoded developer URLs may ship in the production shell. */
    public function testNoHardcodedLocalhostUrlsLeakIntoTheShell(): void
    {
        $html = $this->render();

        self::assertStringNotContainsString('localhost:8000', $html);
        self::assertStringNotContainsString('localhost:8051', $html);
        self::assertStringNotContainsString('localhost:3000', $html);
    }
}
