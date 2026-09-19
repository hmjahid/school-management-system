<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentFlowRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(string $permission = 'viewResults'): User
    {
        $role = Role::findOrCreate('admin');
        $role->givePermissionTo(Permission::findOrCreate($permission));

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function seedStudents(): array
    {
        $session = AcademicSession::create([
            'name' => '2026',
            'code' => '2026',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);
        $class = SchoolClass::create(['name' => 'Class One']);
        $batch = Batch::create(['name' => 'Batch A']);

        $students = [];
        foreach (['1001', '1002', '1003'] as $i => $roll) {
            $user = User::factory()->create();
            $student = Student::create([
                'user_id' => $user->id,
                'class_id' => $class->id,
                'batch_id' => $batch->id,
                'roll_number' => $roll,
                'admission_number' => 'R-'.$roll,
                'admission_date' => now()->toDateString(),
                'first_name' => 'Test',
                'last_name' => 'Student '.($i + 1),
            ]);
            $students[] = $student;
        }

        $exam = Exam::create([
            'name' => 'Final Term 2026',
            'code' => 'FINAL-2026',
            'is_published' => true,
            'start_date' => now()->addDays(7),
            'total_marks' => 100,
            'passing_marks' => 40,
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
        ]);

        return compact('exam', 'students', 'class', 'batch');
    }

    public function test_seat_plan_preview_generates_for_published_exam(): void
    {
        [$exam] = array_values($this->seedStudents());
        $user = $this->admin();

        $response = $this->actingAs($user)->get(route('dashboard.seat-plans.generate', ['exam' => $exam->id, 'view' => 1]));

        $response->assertStatus(200);
        $content = (string) $response->getContent();
        $this->assertStringContainsString('Room-1', $content);
        $this->assertStringContainsString('1001', $content);
    }

    public function test_progress_report_preview_generates_for_student(): void
    {
        [, $students] = array_values($this->seedStudents());
        $user = $this->admin();

        $route = route('dashboard.progress-reports.generate', ['student' => $students[0]->id, 'view' => 1]);

        $response = $this->actingAs($user)->get($route);

        $response->assertStatus(200);
        $content = (string) $response->getContent();
        $this->assertStringContainsString('R-1001', $content);
    }

    public function test_seat_plan_pdf_downloads(): void
    {
        [$exam] = array_values($this->seedStudents());
        $user = $this->admin();

        $response = $this->actingAs($user)->get(route('dashboard.seat-plans.generate', ['exam' => $exam->id]));

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type') ?? '');
    }
}
