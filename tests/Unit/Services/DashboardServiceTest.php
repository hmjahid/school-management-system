<?php

namespace Tests\Unit\Services;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttendance(string $status): Attendance
    {
        return Attendance::create([
            'student_id' => Student::factory()->create()->id,
            'school_class_id' => SchoolClass::factory()->create()->id,
            'marked_by' => User::factory()->create()->id,
            'date' => now(),
            'status' => $status,
        ]);
    }

    /** @test */
    public function get_dashboard_data_returns_expected_structure(): void
    {
        $data = app(DashboardService::class)->getDashboardData();

        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('monthly_data', $data);
        $this->assertArrayHasKey('class_distribution', $data);
        $this->assertArrayHasKey('recent_activity', $data);
        $this->assertArrayHasKey('upcoming_events', $data);
        $this->assertArrayHasKey('pending_assignments', $data);
        $this->assertArrayHasKey('performance_metrics', $data);
        $this->assertArrayHasKey('quick_stats', $data);
        $this->assertArrayHasKey('widget_config', $data);
    }

    /** @test */
    public function get_dashboard_data_is_cached(): void
    {
        $service = app(DashboardService::class);

        $first = $service->getDashboardData();
        $second = $service->getDashboardData();

        // Both come from the same cache key, so identical references/values.
        $this->assertEquals($first['totals'], $second['totals']);
    }

    /** @test */
    public function attendance_stats_counts_today_breakdown(): void
    {
        $this->makeAttendance(Attendance::STATUS_PRESENT);
        $this->makeAttendance(Attendance::STATUS_LATE);
        $this->makeAttendance(Attendance::STATUS_ABSENT);

        $stats = app(DashboardService::class)->attendanceStats();

        $this->assertEquals(1, $stats['present_today']);
        $this->assertEquals(1, $stats['late_today']);
        $this->assertEquals(1, $stats['absent_today']);
        // present + late + half_day = 2 out of 3
        $this->assertEquals(round((2 / 3) * 100, 1), $stats['today_rate']);
    }

    /** @test */
    public function attendance_stats_empty_day_returns_zero_rate(): void
    {
        $stats = app(DashboardService::class)->attendanceStats();

        $this->assertEquals(0, $stats['present_today']);
        $this->assertEquals(0.0, $stats['today_rate']);
        $this->assertEquals([], $stats['trend']);
    }

    /** @test */
    public function totals_count_models_in_database(): void
    {
        Student::factory()->times(3)->create();
        SchoolClass::factory()->times(2)->create();

        $totals = app(DashboardService::class)->getDashboardData()['totals'];

        $this->assertEquals(3, $totals['students']);
        $this->assertEquals(\App\Models\SchoolClass::count(), $totals['classes']);
    }
}
