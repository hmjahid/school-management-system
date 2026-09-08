<?php

namespace Tests\Unit\Models;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_correct_status_badge_html(): void
    {
        $exam = Exam::create([
            'name' => 'Test Exam',
            'status' => Exam::STATUS_DRAFT,
            'total_marks' => 100,
            'passing_marks' => 40,
        ]);

        $this->assertStringContainsString('badge bg-secondary', $exam->status_badge);
        $this->assertStringContainsString('Draft', $exam->status_badge);
    }

    #[Test]
    public function it_detects_upcoming_status(): void
    {
        $exam = Exam::create([
            'name' => 'Future Exam',
            'status' => Exam::STATUS_SCHEDULED,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(7)->addHours(2),
        ]);

        $this->assertTrue($exam->is_upcoming);
    }

    #[Test]
    public function it_detects_ongoing_status(): void
    {
        $exam = Exam::create([
            'name' => 'Ongoing Exam',
            'status' => Exam::STATUS_ONGOING,
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);

        $this->assertTrue($exam->is_ongoing);
    }

    #[Test]
    public function it_detects_ongoing_by_dates_even_without_status(): void
    {
        $exam = Exam::create([
            'name' => 'Date-Ongoing Exam',
            'status' => Exam::STATUS_DRAFT,
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);

        $this->assertTrue($exam->is_ongoing);
    }

    #[Test]
    public function it_detects_completed_status(): void
    {
        $exam = Exam::create([
            'name' => 'Completed Exam',
            'status' => Exam::STATUS_COMPLETED,
            'start_date' => now()->subDays(7),
            'end_date' => now()->subDays(6),
        ]);

        $this->assertTrue($exam->is_completed);
    }

    #[Test]
    public function it_detects_completed_by_past_end_date(): void
    {
        $exam = Exam::create([
            'name' => 'Past Exam',
            'status' => Exam::STATUS_DRAFT,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(9),
        ]);

        $this->assertTrue($exam->is_completed);
    }

    #[Test]
    public function is_fully_published_requires_both_status_and_flag(): void
    {
        $notPublished = Exam::create([
            'name' => 'Draft Exam',
            'status' => Exam::STATUS_DRAFT,
            'is_published' => false,
        ]);
        $this->assertFalse($notPublished->isFullyPublished());

        $statusOnly = Exam::create([
            'name' => 'Status Only',
            'status' => Exam::STATUS_PUBLISHED,
            'is_published' => false,
        ]);
        $this->assertFalse($statusOnly->isFullyPublished());

        $flagOnly = Exam::create([
            'name' => 'Flag Only',
            'status' => Exam::STATUS_COMPLETED,
            'is_published' => true,
        ]);
        $this->assertFalse($flagOnly->isFullyPublished());

        $both = Exam::create([
            'name' => 'Fully Published',
            'status' => Exam::STATUS_PUBLISHED,
            'is_published' => true,
        ]);
        $this->assertTrue($both->isFullyPublished());
    }

    #[Test]
    public function it_formats_duration_correctly(): void
    {
        $noDuration = Exam::create(['name' => 'No Duration', 'duration' => null]);
        $this->assertEquals('N/A', $noDuration->duration_formatted);

        $minutesOnly = Exam::create(['name' => '45 Min', 'duration' => 45]);
        $this->assertEquals('45 minutes', $minutesOnly->duration_formatted);

        $hoursOnly = Exam::create(['name' => '2 Hours', 'duration' => 120]);
        $this->assertEquals('2 hours', $hoursOnly->duration_formatted);

        $both = Exam::create(['name' => '1h 30m', 'duration' => 90]);
        $this->assertEquals('1 hour 30 minutes', $both->duration_formatted);
    }

    #[Test]
    public function it_calculates_grade_using_default_scale(): void
    {
        $exam = Exam::create([
            'name' => 'Grade Exam',
            'total_marks' => 100,
            'passing_marks' => 40,
        ]);

        $aPlus = $exam->calculateGrade(85);
        $this->assertEquals('A+', $aPlus['grade']);
        $this->assertEquals(4.0, $aPlus['points']);

        $a = $exam->calculateGrade(75);
        $this->assertEquals('A', $a['grade']);

        $fail = $exam->calculateGrade(30);
        $this->assertEquals('F', $fail['grade']);
        $this->assertEquals(0.0, $fail['points']);
    }

    #[Test]
    public function it_calculates_grade_using_custom_scale(): void
    {
        $customScale = [
            ['min' => 90, 'max' => 100, 'grade' => 'S', 'points' => 5.0, 'remark' => 'Superior'],
            ['min' => 0, 'max' => 89, 'grade' => 'U', 'points' => 0.0, 'remark' => 'Unsatisfactory'],
        ];

        $exam = Exam::create([
            'name' => 'Custom Scale Exam',
            'grading_scale' => $customScale,
        ]);

        $superior = $exam->calculateGrade(95);
        $this->assertEquals('S', $superior['grade']);
        $this->assertEquals(5.0, $superior['points']);

        $unsatisfactory = $exam->calculateGrade(50);
        $this->assertEquals('U', $unsatisfactory['grade']);
    }

    #[Test]
    public function it_returns_default_fail_when_no_grade_matches(): void
    {
        $exam = Exam::create([
            'name' => 'No Match Exam',
            'grading_scale' => [
                ['min' => 50, 'max' => 100, 'grade' => 'P', 'points' => 1.0, 'remark' => 'Pass'],
            ],
        ]);

        $result = $exam->calculateGrade(30);
        $this->assertEquals('F', $result['grade']);
        $this->assertEquals(0.0, $result['points']);
    }

    #[Test]
    public function it_returns_zeroed_statistics_when_no_results(): void
    {
        $exam = Exam::create([
            'name' => 'Empty Exam',
            'total_marks' => 100,
            'passing_marks' => 40,
        ]);

        $stats = $exam->getStatistics();

        $this->assertEquals(0, $stats['total_students']);
        $this->assertEquals(0, $stats['passed']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['average_score']);
        $this->assertEquals([], $stats['grade_distribution']);
    }

    #[Test]
    public function it_calculates_statistics_with_results(): void
    {
        $exam = Exam::create([
            'name' => 'Stats Exam',
            'total_marks' => 100,
            'passing_marks' => 40,
        ]);

        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create();

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
            'obtained_marks' => 85,
            'status' => ExamResult::STATUS_PASSED,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student2->id,
            'obtained_marks' => 30,
            'status' => ExamResult::STATUS_FAILED,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student3->id,
            'obtained_marks' => null,
            'status' => ExamResult::STATUS_ABSENT,
        ]);

        $stats = $exam->getStatistics();

        $this->assertEquals(3, $stats['total_students']);
        $this->assertEquals(2, $stats['participated']);
        $this->assertEquals(1, $stats['passed']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(85, $stats['highest_score']);
        $this->assertEquals(30, $stats['lowest_score']);
        $this->assertEquals(50.0, $stats['pass_rate']);
    }

    #[Test]
    public function it_checks_if_student_has_taken_exam(): void
    {
        $exam = Exam::create([
            'name' => 'Check Exam',
            'total_marks' => 100,
            'passing_marks' => 40,
        ]);

        $student = Student::factory()->create();

        $this->assertFalse($exam->hasStudentTakenExam($student->id));

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 75,
            'status' => ExamResult::STATUS_PASSED,
        ]);

        $this->assertTrue($exam->hasStudentTakenExam($student->id));
    }

    #[Test]
    public function scope_published_filters_correctly(): void
    {
        Exam::create(['name' => 'Not Published', 'status' => Exam::STATUS_PUBLISHED, 'is_published' => false]);
        Exam::create(['name' => 'Published', 'status' => Exam::STATUS_PUBLISHED, 'is_published' => true]);
        Exam::create(['name' => 'Completed', 'status' => Exam::STATUS_COMPLETED, 'is_published' => true]);

        $published = Exam::published()->get();

        $this->assertCount(1, $published);
        $this->assertEquals('Published', $published->first()->name);
    }

    #[Test]
    public function it_returns_type_and_status_arrays(): void
    {
        $types = Exam::getTypes();
        $this->assertArrayHasKey(Exam::TYPE_QUIZ, $types);
        $this->assertArrayHasKey(Exam::TYPE_FINAL, $types);

        $statuses = Exam::getStatuses();
        $this->assertArrayHasKey(Exam::STATUS_DRAFT, $statuses);
        $this->assertArrayHasKey(Exam::STATUS_PUBLISHED, $statuses);

        $gradingTypes = Exam::getGradingTypes();
        $this->assertArrayHasKey(Exam::GRADING_PERCENTAGE, $gradingTypes);
    }
}
