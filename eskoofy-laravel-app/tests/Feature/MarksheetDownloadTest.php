<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarksheetDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function exam(): Exam
    {
        return Exam::create([
            'name' => 'Final Exam',
            'code' => 'FINAL-'.now()->timestamp,
            'type' => Exam::TYPE_FINAL,
            'status' => Exam::STATUS_COMPLETED,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(1),
            'total_marks' => 100,
            'passing_marks' => 33,
            'grading_type' => Exam::GRADING_GRADE,
        ]);
    }

    private function createResult(Exam $exam, Student $student): ExamResult
    {
        return ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 80,
            'grade' => 'A',
            'grade_point' => 4.0,
            'status' => ExamResult::STATUS_PASSED,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    private function admin(): User
    {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo(Permission::findOrCreate('manage_exams', 'web'));

        return $user;
    }

    private function createStudent(?int $userId = null): Student
    {
        $class = SchoolClass::create(['name' => 'Class One']);

        return Student::create([
            'user_id' => $userId ?? User::factory()->create()->id,
            'class_id' => $class->id,
            'admission_number' => 'ADM'.uniqid(),
            'admission_date' => now()->toDateString(),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
    }

    public function test_admin_can_download_marksheet_as_pdf(): void
    {
        $admin = $this->admin();
        $exam = $this->exam();
        $student = $this->createStudent();
        $result = $this->createResult($exam, $student);

        $response = $this->actingAs($admin)
            ->get(route('dashboard.exams.results.marksheet', [$exam, $result]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertSee('%PDF', false);
    }

    public function test_student_can_download_own_marksheet_as_pdf(): void
    {
        $studentUser = User::factory()->create();
        $studentUser->assignRole('student');
        $student = $this->createStudent($studentUser->id);

        $exam = $this->exam();
        $result = $this->createResult($exam, $student);

        $response = $this->actingAs($studentUser)
            ->get(route('dashboard.exams.results.marksheet', [$exam, $result]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_student_cannot_download_another_students_marksheet(): void
    {
        $studentUser = User::factory()->create();
        $studentUser->assignRole('student');
        $this->createStudent($studentUser->id);

        $exam = $this->exam();
        $other = $this->createStudent();
        $result = $this->createResult($exam, $other);

        $this->actingAs($studentUser)
            ->get(route('dashboard.exams.results.marksheet', [$exam, $result]))
            ->assertStatus(403);
    }
}
