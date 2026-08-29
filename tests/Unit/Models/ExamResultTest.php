<?php

namespace Tests\Unit\Models;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamResultTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_correct_status_badge_class(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        $pending = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => ExamResult::STATUS_PENDING,
        ]);
        $this->assertEquals('bg-yellow-100 text-yellow-800', $pending->status_badge);

        $passed = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => Student::factory()->create()->id,
            'status' => ExamResult::STATUS_PASSED,
        ]);
        $this->assertEquals('bg-green-100 text-green-800', $passed->status_badge);

        $failed = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => Student::factory()->create()->id,
            'status' => ExamResult::STATUS_FAILED,
        ]);
        $this->assertEquals('bg-red-100 text-red-800', $failed->status_badge);
    }

    /** @test */
    public function it_returns_correct_status_label(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => ExamResult::STATUS_PASSED,
        ]);

        $this->assertEquals('Passed', $result->status_label);
    }

    /** @test */
    public function it_returns_unknown_label_for_invalid_status(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'invalid_status',
        ]);

        $this->assertEquals('Unknown', $result->status_label);
    }

    /** @test */
    public function it_calculates_passed_status_from_marks(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 75,
            'status' => ExamResult::STATUS_PENDING,
        ]);

        $this->assertEquals(ExamResult::STATUS_PASSED, $result->calculateStatus());
    }

    /** @test */
    public function it_calculates_failed_status_from_marks(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 30,
            'status' => ExamResult::STATUS_PENDING,
        ]);

        $this->assertEquals(ExamResult::STATUS_FAILED, $result->calculateStatus());
    }

    /** @test */
    public function it_preserves_absent_status_during_calculation(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => null,
            'status' => ExamResult::STATUS_ABSENT,
        ]);

        $this->assertEquals(ExamResult::STATUS_ABSENT, $result->calculateStatus());
    }

    /** @test */
    public function it_preserves_malpractice_status_during_calculation(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 50,
            'status' => ExamResult::STATUS_MALPRACTICE,
        ]);

        $this->assertEquals(ExamResult::STATUS_MALPRACTICE, $result->calculateStatus());
    }

    /** @test */
    public function it_publishes_result(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();
        $staff = User::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 75,
            'status' => ExamResult::STATUS_PASSED,
        ]);

        $this->assertTrue($result->publish($staff->id, 'Published after review'));
        $result->refresh();

        $this->assertTrue($result->is_published);
        $this->assertNotNull($result->published_at);
        $this->assertEquals($staff->id, $result->published_by);
        $this->assertEquals('Published after review', $result->publish_remarks);
    }

    /** @test */
    public function it_unpublishes_result(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();
        $staff = User::factory()->create();

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 75,
            'status' => ExamResult::STATUS_PASSED,
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $staff->id,
        ]);

        $this->assertTrue($result->unpublish($staff->id, 'Correction needed'));
        $result->refresh();

        $this->assertFalse($result->is_published);
        $this->assertNull($result->published_at);
        $this->assertNull($result->published_by);
        $this->assertEquals('Correction needed', $result->unpublish_remarks);
        $this->assertEquals($staff->id, $result->unpublished_by);
        $this->assertNotNull($result->unpublished_at);
    }

    /** @test */
    public function scope_published_filters_published_results(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);
        $student = Student::factory()->create();

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 75,
            'status' => ExamResult::STATUS_PASSED,
            'is_published' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => Student::factory()->create()->id,
            'obtained_marks' => 30,
            'status' => ExamResult::STATUS_FAILED,
            'is_published' => false,
        ]);

        $published = ExamResult::published()->get();
        $this->assertCount(1, $published);
    }

    /** @test */
    public function scope_passed_filters_correctly(): void
    {
        $exam = Exam::create(['name' => 'Test', 'total_marks' => 100, 'passing_marks' => 40]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => Student::factory()->create()->id,
            'obtained_marks' => 75,
            'status' => ExamResult::STATUS_PASSED,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => Student::factory()->create()->id,
            'obtained_marks' => 30,
            'status' => ExamResult::STATUS_FAILED,
        ]);

        $passed = ExamResult::passed()->get();
        $this->assertCount(1, $passed);
    }
}
