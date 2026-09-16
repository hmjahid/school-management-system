<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Guards the Phase-7 php API expansion parity with eskoofy-app routes/api.php:
 * auth (token pair), academic/content/careers/legal public endpoints, teacher
 * portal, search, fee-payment sub-routes, admin CMS/widgets/settings, and the
 * scheduler services (recurring + scheduled notifications).
 */
class ApiExpansionParityTest extends PHPUnitTestCase
{
    private function apiRoutes(): string
    {
        $path = dirname(__DIR__, 2) . '/routes/api.php';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    private function schema(): string
    {
        $path = dirname(__DIR__, 2) . '/database/schema.sql';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    private function hasRoute(string $needle): bool
    {
        return str_contains($this->apiRoutes(), $needle);
    }

    public function test_token_auth_routes_registered(): void
    {
        $this->assertTrue($this->hasRoute("post('/api/v1/auth/login'"));
        $this->assertTrue($this->hasRoute("post('/api/v1/auth/register'"));
        $this->assertTrue($this->hasRoute("post('/api/v1/auth/refresh-token'"));
        $this->assertTrue($this->hasRoute("post('/logout', 'App\\\\Controllers\\\\Api\\\\AuthController', 'logout')"));
        $this->assertTrue($this->hasRoute("get('/me', 'App\\\\Controllers\\\\Api\\\\AuthController', 'me')"));
    }

    public function test_personal_access_tokens_table_in_schema(): void
    {
        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `personal_access_tokens`',
            $this->schema()
        );
        $this->assertStringContainsString(
            'personal_access_tokens_token_unique',
            $this->schema()
        );
    }

    public function test_token_middleware_exists_and_reads_bearer(): void
    {
        $path = dirname(__DIR__, 2) . '/app/Core/Middleware/ApiTokenMiddleware.php';
        $this->assertFileExists($path);
        $src = (string) file_get_contents($path);
        $this->assertStringContainsString('Authorization', $src);
        $this->assertStringContainsString('Bearer', $src);
        $this->assertStringContainsString('PersonalAccessToken', $src);
    }

    public function test_public_content_endpoints_registered(): void
    {
        foreach ([
            'academics/curriculum',
            'academics/programs',
            'academics/faculty',
            'academics/results/filters',
            'news/categories',
            'news/upcoming-events',
            'website-content/pages',
            'website/gallery',
            'careers/apply',
            'terms',
            'privacy',
            'sitemap',
            'home',
        ] as $path) {
            $this->assertTrue(
                $this->hasRoute("'/api/v1/{$path}'"),
                'missing public route: /api/v1/' . $path
            );
        }
    }

    public function test_teacher_portal_routes_registered(): void
    {
        $this->assertTrue($this->hasRoute("'/classes', 'App\\\\Controllers\\\\Api\\\\TeacherController', 'getTeacherClasses'"));
        $this->assertTrue($this->hasRoute("'/classes/{classId}/students'"));
        $this->assertTrue($this->hasRoute("'/classes/{classId}/grades'"));
    }

    public function test_fee_payment_subroutes_registered(): void
    {
        $this->assertTrue($this->hasRoute("'/statuses', 'App\\\\Controllers\\\\Api\\\\FeePaymentController', 'getStatuses'"));
        $this->assertTrue($this->hasRoute("'/methods', 'App\\\\Controllers\\\\Api\\\\FeePaymentController', 'getPaymentMethods'"));
        $this->assertTrue($this->hasRoute("'/{payment}/approve', 'App\\\\Controllers\\\\Api\\\\FeePaymentController', 'approve'"));
        $this->assertTrue($this->hasRoute("'/{payment}/cancel', 'App\\\\Controllers\\\\Api\\\\FeePaymentController', 'cancel'"));
        $this->assertTrue($this->hasRoute("get('/types', 'App\\\\Controllers\\\\Api\\\\FeeController', 'getFeeTypes')"));
        $this->assertTrue($this->hasRoute("'/{fee}/payments', 'App\\\\Controllers\\\\Api\\\\FeeController', 'feePayments'"));
    }

    public function test_admin_routes_registered(): void
    {
        foreach ([
            "'/dashboard', 'App\\\\Controllers\\\\Api\\\\AdminController', 'dashboard'",
            "'/analytics/overview'",
            "'/activity', 'App\\\\Controllers\\\\Api\\\\AdminController', 'activity'",
            "'/cms/pages', 'App\\\\Controllers\\\\Api\\\\CmsController', 'pages'",
            "'/cms/blocks', 'App\\\\Controllers\\\\Api\\\\CmsController', 'contentBlocks'",
            "'/widgets', 'App\\\\Controllers\\\\Api\\\\AdminController', 'getWidgetConfig'",
            "'/website-settings', 'App\\\\Controllers\\\\Api\\\\WebsiteSettingController', 'index'",
        ] as $needle) {
            $this->assertTrue($this->hasRoute($needle), 'missing admin route: ' . $needle);
        }
    }

    public function test_refund_webhook_route_registered(): void
    {
        $this->assertTrue($this->hasRoute("'/api/webhooks/{gateway}/refund'"));
    }

    public function test_scheduler_services_exist(): void
    {
        $base = dirname(__DIR__, 2) . '/app/Services';
        $this->assertFileExists($base . '/RecurringPaymentService.php');
        $this->assertFileExists($base . '/NotificationService.php');
        $this->assertFileExists($base . '/Notification/ScheduledNotificationService.php');
        $this->assertFileExists($base . '/Push/FirebasePushService.php');
        $this->assertFileExists($base . '/Push/LogPushService.php');
        $this->assertFileExists($base . '/Push/PushNotificationService.php');
    }

    public function test_cron_entry_point_exists(): void
    {
        $this->assertFileExists(dirname(__DIR__, 2) . '/public/cron.php');
    }

    public function test_phase7_models_exist(): void
    {
        $base = dirname(__DIR__, 2) . '/app/Models';
        $this->assertFileExists($base . '/Course.php');
        $this->assertFileExists($base . '/Grade.php');
        $this->assertFileExists($base . '/UserWidgetPreference.php');
        $this->assertFileExists($base . '/PersonalAccessToken.php');
    }

    public function test_courses_and_grades_tables_in_schema(): void
    {
        $schema = $this->schema();
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `courses`', $schema);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `grades`', $schema);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `user_widget_preferences`', $schema);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `scheduled_notifications`', $schema);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `recurring_payment_profiles`', $schema);
    }
}