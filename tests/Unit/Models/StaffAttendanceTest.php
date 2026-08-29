<?php

namespace Tests\Unit\Models;

use App\Models\StaffAttendance;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTeacher(): Teacher
    {
        return Teacher::create(['user_id' => User::factory()->create()->id]);
    }

    /** @test */
    public function it_persists_required_columns(): void
    {
        $teacher = $this->makeTeacher();
        $date = '2026-03-01';

        $attendance = StaffAttendance::create([
            'teacher_id' => $teacher->id,
            'date' => $date,
            'status' => StaffAttendance::STATUS_PRESENT,
        ]);

        $this->assertDatabaseHas('staff_attendances', [
            'teacher_id' => $teacher->id,
            'status' => StaffAttendance::STATUS_PRESENT,
        ]);
        $this->assertEquals('2026-03-01', $attendance->date->toDateString());
    }

    /** @test */
    public function it_casts_date_and_timestamps(): void
    {
        $teacher = $this->makeTeacher();

        $attendance = StaffAttendance::create([
            'teacher_id' => $teacher->id,
            'date' => '2026-03-02',
            'status' => StaffAttendance::STATUS_LATE,
            'check_in_at' => '2026-03-02 09:30:00',
            'check_out_at' => '2026-03-02 16:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $attendance->date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $attendance->check_in_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $attendance->check_out_at);
    }

    /** @test */
    public function it_exposes_status_constants(): void
    {
        $this->assertEquals('present', StaffAttendance::STATUS_PRESENT);
        $this->assertEquals('absent', StaffAttendance::STATUS_ABSENT);
        $this->assertEquals('late', StaffAttendance::STATUS_LATE);
        $this->assertEquals('leave', StaffAttendance::STATUS_LEAVE);
        $this->assertArrayHasKey('present', StaffAttendance::STATUSES);
    }

    /** @test */
    public function it_belongs_to_a_teacher(): void
    {
        $teacher = $this->makeTeacher();

        $attendance = StaffAttendance::create([
            'teacher_id' => $teacher->id,
            'date' => '2026-03-03',
            'status' => StaffAttendance::STATUS_ABSENT,
        ]);

        $this->assertTrue($attendance->teacher->is($teacher));
    }

    /** @test */
    public function it_belongs_to_a_recorder_when_set(): void
    {
        $teacher = $this->makeTeacher();
        $recorder = User::factory()->create();

        $attendance = StaffAttendance::create([
            'teacher_id' => $teacher->id,
            'date' => '2026-03-04',
            'status' => StaffAttendance::STATUS_PRESENT,
            'recorded_by' => $recorder->id,
        ]);

        $this->assertTrue($attendance->recorder->is($recorder));
    }

    /** @test */
    public function it_defaults_status_to_present(): void
    {
        $teacher = $this->makeTeacher();

        $attendance = StaffAttendance::create([
            'teacher_id' => $teacher->id,
            'date' => '2026-03-05',
        ]);

        $this->assertDatabaseHas('staff_attendances', [
            'id' => $attendance->id,
            'status' => 'present',
        ]);
    }
}
