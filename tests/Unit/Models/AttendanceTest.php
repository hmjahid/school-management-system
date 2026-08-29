<?php

namespace Tests\Unit\Models;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeBatch(): Batch
    {
        return Batch::create(['name' => 'Test Batch', 'code' => 'TB'.uniqid()]);
    }

    protected function makeAttendance(Student $student, array $overrides = []): Attendance
    {
        $class = SchoolClass::factory()->create();
        $markedBy = User::factory()->create();

        return Attendance::create(array_merge([
            'student_id' => $student->id,
            'school_class_id' => $class->id,
            'marked_by' => $markedBy->id,
            'date' => today(),
            'status' => Attendance::STATUS_PRESENT,
        ], $overrides));
    }

    /** @test */
    public function it_returns_correct_status_badge(): void
    {
        $present = new Attendance(['status' => Attendance::STATUS_PRESENT]);
        $this->assertStringContainsString('badge bg-success', $present->status_badge);

        $absent = new Attendance(['status' => Attendance::STATUS_ABSENT]);
        $this->assertStringContainsString('badge bg-danger', $absent->status_badge);

        $late = new Attendance(['status' => Attendance::STATUS_LATE]);
        $this->assertStringContainsString('badge bg-warning', $late->status_badge);
    }

    /** @test */
    public function it_appends_correct_boolean_accessors(): void
    {
        $present = new Attendance(['status' => Attendance::STATUS_PRESENT]);
        $this->assertTrue($present->is_present);
        $this->assertFalse($present->is_absent);

        $absent = new Attendance(['status' => Attendance::STATUS_ABSENT]);
        $this->assertTrue($absent->is_absent);
        $this->assertFalse($absent->is_present);

        $late = new Attendance(['status' => Attendance::STATUS_LATE]);
        $this->assertTrue($late->is_late);

        $halfDay = new Attendance(['status' => Attendance::STATUS_HALF_DAY]);
        $this->assertTrue($halfDay->is_half_day);
    }

    /** @test */
    public function it_returns_statuses_and_types_arrays(): void
    {
        $statuses = Attendance::getStatuses();
        $this->assertArrayHasKey(Attendance::STATUS_PRESENT, $statuses);
        $this->assertArrayHasKey(Attendance::STATUS_LEAVE, $statuses);

        $types = Attendance::getTypes();
        $this->assertArrayHasKey(Attendance::TYPE_DAILY, $types);
        $this->assertArrayHasKey(Attendance::TYPE_SUBJECT_WISE, $types);
    }

    /** @test */
    public function it_calculates_student_attendance_percentage(): void
    {
        $student = Student::factory()->create();

        $this->makeAttendance($student, ['date' => today(), 'status' => Attendance::STATUS_PRESENT]);
        $this->makeAttendance($student, ['date' => today()->subDay(), 'status' => Attendance::STATUS_PRESENT]);
        $this->makeAttendance($student, ['date' => today()->subDays(2), 'status' => Attendance::STATUS_ABSENT]);
        $this->makeAttendance($student, ['date' => today()->subDays(3), 'status' => Attendance::STATUS_LATE]);

        $percentage = Attendance::getStudentAttendancePercentage($student->id);

        // 3 out of 4 are "present" (present + late + half_day count as present)
        $this->assertEquals(75.0, $percentage);
    }

    /** @test */
    public function it_returns_zero_percentage_for_no_records(): void
    {
        $student = Student::factory()->create();

        $percentage = Attendance::getStudentAttendancePercentage($student->id);

        $this->assertEquals(0.0, $percentage);
    }

    /** @test */
    public function it_counts_half_day_as_present_in_percentage(): void
    {
        $student = Student::factory()->create();

        $this->makeAttendance($student, ['date' => today(), 'status' => Attendance::STATUS_HALF_DAY]);
        $this->makeAttendance($student, ['date' => today()->subDay(), 'status' => Attendance::STATUS_ABSENT]);

        $percentage = Attendance::getStudentAttendancePercentage($student->id);

        $this->assertEquals(50.0, $percentage);
    }

    /** @test */
    public function it_returns_attendance_summary(): void
    {
        $batch = $this->makeBatch();
        $student = Student::factory()->create();

        $this->makeAttendance($student, ['batch_id' => $batch->id, 'date' => today(), 'status' => Attendance::STATUS_PRESENT]);
        $this->makeAttendance($student, ['batch_id' => $batch->id, 'date' => today()->subDay(), 'status' => Attendance::STATUS_ABSENT]);
        $this->makeAttendance($student, ['batch_id' => $batch->id, 'date' => today()->subDays(2), 'status' => Attendance::STATUS_LATE]);
        $this->makeAttendance($student, ['batch_id' => $batch->id, 'date' => today()->subDays(3), 'status' => Attendance::STATUS_LEAVE]);
        $this->makeAttendance($student, ['batch_id' => $batch->id, 'date' => today()->subDays(4), 'status' => Attendance::STATUS_HOLIDAY]);

        $summary = Attendance::getAttendanceSummary($batch->id);

        $this->assertEquals(5, $summary['total']);
        $this->assertEquals(2, $summary['present']); // present + late
        $this->assertEquals(1, $summary['absent']);
        $this->assertEquals(1, $summary['late']);
        $this->assertEquals(1, $summary['on_leave']);
        $this->assertEquals(1, $summary['holiday']);
        $this->assertEquals(40.0, $summary['attendance_percentage']);
    }

    /** @test */
    public function it_filters_by_batch_in_percentage_calculation(): void
    {
        $batch1 = $this->makeBatch();
        $batch2 = $this->makeBatch();
        $student = Student::factory()->create();

        $this->makeAttendance($student, ['batch_id' => $batch1->id, 'date' => today(), 'status' => Attendance::STATUS_PRESENT]);
        $this->makeAttendance($student, ['batch_id' => $batch1->id, 'date' => today()->subDay(), 'status' => Attendance::STATUS_ABSENT]);
        $this->makeAttendance($student, ['batch_id' => $batch2->id, 'date' => today()->subDays(10), 'status' => Attendance::STATUS_PRESENT]);

        $batch1Percentage = Attendance::getStudentAttendancePercentage($student->id, $batch1->id);
        $this->assertEquals(50.0, $batch1Percentage);

        $batch2Percentage = Attendance::getStudentAttendancePercentage($student->id, $batch2->id);
        $this->assertEquals(100.0, $batch2Percentage);
    }

    /** @test */
    public function scope_status_filters_correctly(): void
    {
        $student = Student::factory()->create();

        $this->makeAttendance($student, ['date' => today(), 'status' => Attendance::STATUS_PRESENT]);
        $this->makeAttendance($student, ['date' => today()->subDay(), 'status' => Attendance::STATUS_ABSENT]);

        $present = Attendance::status(Attendance::STATUS_PRESENT)->get();
        $this->assertCount(1, $present);

        $absent = Attendance::status(Attendance::STATUS_ABSENT)->get();
        $this->assertCount(1, $absent);
    }

    /** @test */
    public function scope_date_range_filters_correctly(): void
    {
        $student = Student::factory()->create();

        $this->makeAttendance($student, ['date' => '2026-01-15', 'status' => Attendance::STATUS_PRESENT]);
        $this->makeAttendance($student, ['date' => '2026-01-20', 'status' => Attendance::STATUS_PRESENT]);
        $this->makeAttendance($student, ['date' => '2026-01-25', 'status' => Attendance::STATUS_PRESENT]);

        $range = Attendance::dateRange('2026-01-17', '2026-01-22')->get();
        $this->assertCount(1, $range);
    }
}
