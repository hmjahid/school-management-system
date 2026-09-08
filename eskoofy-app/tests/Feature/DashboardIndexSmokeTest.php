<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardIndexSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['email' => 'admin@smoke.test']);
        $this->admin->assignRole('admin');

        // Grant broad permissions so index pages aren't gated by permission checks.
        $permissions = [
            'view_students', 'view_fee_payments', 'view_admissions', 'view_exams',
            'view_attendance', 'view_teachers', 'view_letters', 'view_library',
            'view_transport', 'view_hostel', 'view_payroll', 'manage_calendar',
            'view_documents', 'view_media', 'view_notices', 'view_news',
            'view_gallery', 'view_backups', 'view_audit_log', 'view_reports',
            'view_events', 'view_visitor_logs', 'view_messages', 'send_bulk_sms',
            'send_email', 'manage_permissions', 'manage_roles',
        ];
        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
            $this->admin->givePermissionTo($perm);
        }
    }

    #[Test]
    public function all_dashboard_index_routes_render_200(): void
    {
        $routes = [
            'dashboard',
            'dashboard.students',
            'dashboard.fee-payments.index',
            'dashboard.admissions.index',
            'dashboard.exams',
            'dashboard.attendance',
            'dashboard.teachers',
            'dashboard.library.books.index',
            'dashboard.transport.routes.index',
            'dashboard.hostels.index',
            'dashboard.payroll.generate',
            'dashboard.documents.index',
            'dashboard.media.index',
            'dashboard.notices.index',
            'dashboard.news.index',
            'dashboard.gallery.index',
            'dashboard.backup.index',
            'dashboard.activity.index',
            'dashboard.reports',
            'dashboard.events',
            'dashboard.visitor-logs.index',
            'dashboard.sms.index',
            'dashboard.roles.index',
            'dashboard.permissions.index',
            'dashboard.careers.index',
        ];

        foreach ($routes as $route) {
            $path = route($route);

            $response = $this->actingAs($this->admin)->get($path);

            $this->assertSame(
                200,
                $response->status(),
                "Route {$route} → {$path} returned {$response->status()}. ".substr(strip_tags((string) $response->getContent()), 0, 300)
            );
        }
    }
}
