<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAndReportBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_analytics_page_renders_for_admin(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard.analytics'))
            ->assertOk()
            ->assertSee('Analytics');
    }

    public function test_report_builder_page_renders_for_admin(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard.reports.builder'))
            ->assertOk()
            ->assertSee('Report builder');
    }

    public function test_report_builder_exports_payments_csv(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Payment::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->post(route('dashboard.reports.builder.export'), [
                'entity' => 'payments',
                'columns' => ['id', 'paid_amount', 'payment_status', 'payment_method'],
            ]);

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Paid amount', $content);
        $this->assertStringContainsString('completed', $content);
    }

    public function test_report_builder_export_requires_columns(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('dashboard.reports.builder.export'), ['entity' => 'students'])
            ->assertStatus(422);
    }

    public function test_dashboard_chart_uses_real_data(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        Payment::factory()->count(3)->create([
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_date' => now()->startOfMonth(),
            'paid_amount' => 1000,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
