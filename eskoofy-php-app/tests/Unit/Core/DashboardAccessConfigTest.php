<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

/**
 * Guards the dashboard authorization config (config/access.php) so a new
 * Dashboard controller cannot silently become accessible to every role.
 */
class DashboardAccessConfigTest extends TestCase
{
    private function access(): array
    {
        $path = dirname(__DIR__, 3) . '/config/access.php';
        $this->assertFileExists($path, 'config/access.php must exist');

        return require $path;
    }

    public function test_dashboard_roles_exclude_students_and_guardians(): void
    {
        $access = $this->access();
        $roles = $access['dashboard_roles'] ?? [];
        $this->assertNotEmpty($roles);
        $this->assertNotContains('student', $roles, 'students must not access /dashboard');
        $this->assertNotContains('guardian', $roles, 'guardians must not access /dashboard');
        $this->assertContains('admin', $roles);
    }

    public function test_sensitive_modules_require_admin(): void
    {
        $access = $this->access();
        $map = $access['module_roles'] ?? [];
        foreach (['UserController', 'RoleController', 'PermissionController', 'SettingController', 'BackupController', 'SmsController', 'PayrollController', 'PaymentGatewayController'] as $controller) {
            $this->assertArrayHasKey($controller, $map, "{$controller} must be explicitly mapped");
            $this->assertContains('admin', $map[$controller], "{$controller} must require admin");
            $this->assertNotContains('student', $map[$controller]);
            $this->assertNotContains('guardian', $map[$controller]);
        }
    }

    public function test_every_roles_value_is_known(): void
    {
        $access = $this->access();
        $known = ['super_admin', 'admin', 'teacher', 'accountant', 'librarian', 'student', 'guardian'];
        foreach (array_merge([$access['dashboard_roles'] ?? []], array_values($access['module_roles'] ?? [])) as $list) {
            foreach ($list as $role) {
                $this->assertContains($role, $known, "unknown role '{$role}' in config/access.php");
            }
        }
    }
}