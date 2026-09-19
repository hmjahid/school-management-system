<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Guards the notifications API parity with eskoofy-laravel-app routes/notifications.php.
 *
 * The API controllers echo JSON and `exit`, so they cannot be driven through
 * the SQL-logging integration harness. Instead we assert at source/route level:
 *
 *   - all nine app notification endpoints are registered with matching methods
 *   - the controller reads real storage (notification_logs + notification_preferences),
 *     never the Laravel `notifications` table (its creation is skipped upstream)
 *   - schema.sql defines the grades + user_widget_preferences tables
 */
class NotificationApiParityTest extends PHPUnitTestCase
{
    private function apiRoutes(): string
    {
        $path = dirname(__DIR__, 2) . '/routes/api.php';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    private function controllerSource(): string
    {
        $path = dirname(__DIR__, 2) . '/app/Controllers/Api/NotificationApiController.php';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    private function schema(): string
    {
        $path = dirname(__DIR__, 2) . '/database/schema.sql';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    public function test_notification_api_routes_match_app(): void
    {
        $routes = $this->apiRoutes();

        $expected = [
            "get('/notifications', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'index')",
            "get('/notifications/unread-count', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'unreadCount')",
            "post('/notifications/{id}/read', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'markAsRead')",
            "post('/notifications/read-all', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'markAllAsRead')",
            "delete('/notifications/{id}', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'destroy')",
            "delete('/notifications', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'clearAll')",
            "get('/notification-preferences', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'getPreferences')",
            "put('/notification-preferences', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'updatePreferences')",
            "get('/notifications/stream', 'App\\\\Controllers\\\\Api\\\\NotificationApiController', 'stream')",
        ];

        foreach ($expected as $signature) {
            $this->assertStringContainsString($signature, $routes,
                'missing app-parity notification route: ' . $signature);
        }
    }

    public function test_controller_reads_notification_logs_not_laravel_notifications_table(): void
    {
        $src = $this->controllerSource();
        $this->assertStringContainsString('notification_logs', $src,
            'real notification storage is notification_logs');
        $this->assertStringContainsString('opened_at', $src,
            'unread is determined by opened_at IS NULL');
        $this->assertStringNotContainsString("FROM notifications", $src,
            'the notifications table is deliberately not created in this app');
    }

    public function test_controller_uses_notification_preference_model(): void
    {
        $src = $this->controllerSource();
        $this->assertStringContainsString('NotificationPreference', $src);
        $this->assertStringContainsString('getUserPreferences', $src);
        $this->assertStringContainsString('setUserPreferences', $src);
    }

    public function test_schema_defines_grades_linked_to_school_classes(): void
    {
        $schema = $this->schema();
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `grades`', $schema);
        $this->assertStringContainsString(
            'grades_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes`',
            $schema
        );
        $this->assertStringContainsString(
            'UNIQUE KEY `grades_student_id_class_id_subject_id_exam_id_unique`',
            $schema
        );
    }

    public function test_schema_defines_user_widget_preferences(): void
    {
        $schema = $this->schema();
        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `user_widget_preferences`',
            $schema
        );
        $this->assertStringContainsString(
            'UNIQUE KEY `user_widget_preferences_user_id_widget_id_unique`',
            $schema
        );
        $this->assertStringContainsString(
            'user_widget_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
            $schema
        );
    }
}