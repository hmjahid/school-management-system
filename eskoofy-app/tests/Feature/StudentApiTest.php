<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Student $student;

    protected Exam $publishedExam;

    protected Exam $draftExam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->admin->assignRole('admin');

        $studentUser = User::factory()->create(['email' => 'student@example.com']);
        $studentUser->assignRole('student');

        $session = AcademicSession::create([
            'name' => 'Session 2025',
            'code' => 'S2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
            'is_current' => true,
        ]);
        $batch = Batch::create(['name' => 'Batch A', 'code' => 'BA', 'academic_session_id' => $session->id, 'is_active' => true]);

        $this->student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'batch_id' => $batch->id,
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2025',
            'session' => '2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_current' => true,
        ]);

        $section = Section::create([
            'name' => 'A',
            'slug' => 'a',
            'academic_year_id' => $academicYear->id,
            'class_id' => $this->student->class_id,
            'is_active' => true,
        ]);
        $this->student->update(['section_id' => $section->id]);

        $this->publishedExam = Exam::create([
            'name' => 'Final Exam',
            'status' => Exam::STATUS_PUBLISHED,
            'is_published' => true,
            'batch_id' => $batch->id,
            'academic_session_id' => $session->id,
            'total_marks' => 100,
        ]);

        $this->draftExam = Exam::create([
            'name' => 'Draft Exam',
            'status' => Exam::STATUS_DRAFT,
            'is_published' => false,
            'batch_id' => $batch->id,
            'academic_session_id' => $session->id,
            'total_marks' => 50,
        ]);

        Attendance::create([
            'student_id' => $this->student->id,
            'school_class_id' => $this->student->class_id,
            'batch_id' => $batch->id,
            'section_id' => $section->id,
            'date' => now()->subDay()->toDateString(),
            'status' => 'present',
            'marked_by' => $this->admin->id,
        ]);

        ExamResult::create([
            'exam_id' => $this->publishedExam->id,
            'student_id' => $this->student->id,
            'obtained_marks' => 85,
            'grade' => 'A',
            'grade_point' => 4,
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->admin->id,
        ]);

        ExamResult::create([
            'exam_id' => $this->draftExam->id,
            'student_id' => $this->student->id,
            'obtained_marks' => 40,
            'grade' => 'C',
            'grade_point' => 2,
            'is_published' => false,
        ]);

        $fee = Fee::create([
            'name' => 'Tuition',
            'code' => 'TUI',
            'class_id' => $this->student->class_id,
            'amount' => 1000,
            'fee_type' => 'tuition',
            'frequency' => 'monthly',
        ]);

        FeePayment::create([
            'invoice_number' => 'INV-001',
            'student_id' => $this->student->id,
            'fee_id' => $fee->id,
            'amount' => 1000,
            'discount_amount' => 0,
            'fine_amount' => 0,
            'paid_amount' => 1000,
            'balance' => 0,
            'payment_date' => now()->toDateString(),
            'month' => now()->month,
            'year' => now()->year,
            'payment_method' => 'cash',
            'status' => 'paid',
            'created_by' => $this->admin->id,
        ]);
    }

    private function url(string $path): string
    {
        return "/api/v1/students/{$this->student->id}{$path}";
    }

    #[Test]
    public function admin_can_list_student_attendance(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson($this->url('/attendance'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'date', 'status', 'student_id'],
                ],
                'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
            ])
            ->assertJsonPath('data.0.status', 'present');
    }

    #[Test]
    public function student_can_view_only_published_results(): void
    {
        $studentUser = $this->student->user;
        $response = $this->actingAs($studentUser, 'sanctum')
            ->getJson($this->url('/results'));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $resultIds = collect($response->json('data'))->pluck('exam_id');
        $this->assertContains($this->publishedExam->id, $resultIds);
        $this->assertNotContains($this->draftExam->id, $resultIds);
    }

    #[Test]
    public function other_student_cannot_view_someone_elses_results(): void
    {
        $other = User::factory()->create();
        $other->assignRole('student');

        $response = $this->actingAs($other, 'sanctum')
            ->getJson($this->url('/results'));

        $response->assertForbidden();
    }

    #[Test]
    public function student_can_view_own_fees_with_summary(): void
    {
        $response = $this->actingAs($this->student->user, 'sanctum')
            ->getJson($this->url('/fees'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.invoice_number', 'INV-001')
            ->assertJsonPath('meta.total_paid', 1000)
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'invoice_number', 'amount', 'paid_amount', 'balance', 'payment_date']],
            ]);
    }

    #[Test]
    public function admin_can_fetch_student_edit_payload(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson($this->url('/edit'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->student->id);
    }
}
