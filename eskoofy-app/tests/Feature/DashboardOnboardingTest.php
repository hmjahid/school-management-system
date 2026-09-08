<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\PaymentGateway;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SetupChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_onboarding_page_renders_for_admin(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard.onboarding'))
            ->assertOk()
            ->assertSee('Setup Guide');
    }

    public function test_setup_checklist_marks_completed_items(): void
    {
        $settings = \App\Models\WebsiteSetting::create([
            'school_name' => 'Demo School',
            'timezone' => 'Asia/Dhaka',
            'established_year' => now()->year,
            'address' => '1 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'postal_code' => '1000',
            'phone' => '+8801700000000',
            'email' => 'school@example.com',
        ]);

        $this->seedFactoryRows();

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());
        $done = $items->filter(fn ($item) => $item['done'])->keyBy('key');

        $this->assertTrue($done->has('school_info'));
        $this->assertTrue($done->has('timezone'));
        $this->assertTrue($done->has('academic_session'));
        $this->assertTrue($done->has('classes'));
        $this->assertTrue($done->has('payment'));
        $this->assertTrue($service->isComplete());
        $this->assertSame(100, $service->completionPercent());
    }

    public function test_dashboard_renders_for_teacher_role(): void
    {
        $teacher = User::factory()->create()->assignRole('teacher');

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My teaching');

        $accountant = User::factory()->create()->assignRole('accountant');

        $this->actingAs($accountant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Finance overview');
    }

    public function test_dashboard_redirects_non_staff_to_portal(): void
    {
        $parent = User::factory()->create()->assignRole('parent');

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertRedirect(route('portal'));
    }

    private function seedFactoryRows(): void
    {
        $session = AcademicSession::create([
            'name' => '2026',
            'code' => '2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);

        SchoolClass::create([
            'name' => 'Class Six',
            'academic_session_id' => $session->id,
        ]);

        PaymentGateway::create([
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
        ]);

        User::factory()->count(3)->create()->each(function ($u) {
            $u->assignRole('teacher');
        });
    }
}
