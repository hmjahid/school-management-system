<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_progress_page(): void
    {
        $spatieRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $roleRow = \App\Models\Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Student One',
            'email' => 'student1@example.com',
            'password' => 'password',
            'role_id' => $roleRow->id,
        ]);
        $user->assignRole($spatieRole);

        $session = AcademicSession::create([
            'name' => '2026',
            'code' => '2026',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(10)->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ]);

        $batch = Batch::withoutGlobalScopes()->create([
            'name' => 'Class 1',
            'academic_session_id' => $session->id,
            'is_active' => true,
        ]);

        $class = SchoolClass::create([
            'name' => 'Class One',
            'code' => 'C1',
            'grade_level' => 1,
            'academic_session_id' => $session->id,
            'is_active' => true,
        ]);

        $student = Student::unguarded(function () use ($user, $batch, $class) {
            return Student::create([
                'user_id' => $user->id,
                'batch_id' => $batch->id,
                'class_id' => $class->id,
                'section_id' => null,
                'guardian_id' => null,
                'admission_number' => 'ADM'.Str::random(8),
                'admission_date' => now()->toDateString(),
                'first_name' => 'Student',
                'last_name' => 'One',
                'gender' => 'male',
                'status' => 'active',
            ]);
        });

        $exam = Exam::create([
            'name' => 'Mid Term',
            'code' => 'MID-1',
            'type' => Exam::TYPE_MID_TERM,
            'status' => Exam::STATUS_PUBLISHED,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(4),
            'total_marks' => 100,
            'passing_marks' => 33,
            'grading_type' => Exam::GRADING_GRADE,
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 80,
            'grade' => 'A',
            'grade_point' => 4.0,
            'status' => ExamResult::STATUS_PASSED,
            'is_published' => true,
        ]);

        $res = $this->actingAs($user)->get(route('portal.progress'));
        $res->assertOk();
        $res->assertSee('Progress report');
        $res->assertSee('Mid Term');
    }
}
