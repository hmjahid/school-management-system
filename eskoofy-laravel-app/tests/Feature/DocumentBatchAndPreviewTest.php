<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentBatchAndPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function makeStudent(): Student
    {
        $class = SchoolClass::create(['name' => 'Class A']);
        $user = User::factory()->create();

        return Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender' => 'male',
            'admission_no' => 'A'.now()->timestamp,
            'admission_number' => 'AN'.now()->timestamp,
            'roll_no' => 'R'.now()->timestamp,
            'roll_number' => 'RL'.now()->timestamp,
            'admission_date' => now()->toDateString(),
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'address' => 'Test',
            'phone' => '01',
            'status' => 'active',
        ]);
    }

    public function test_id_card_batch_create_page_renders(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('dashboard.student-id-cards.batch.create'))
            ->assertOk()
            ->assertSee('Batch options');
    }

    public function test_id_card_batch_generation_creates_cards(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $this->actingAs($admin)
            ->post(route('dashboard.student-id-cards.batch.store'), [
                'class_id' => $student->class_id,
                'issue_date' => now()->toDateString(),
                'details' => ['show_logo' => 1],
            ])
            ->assertRedirect(route('dashboard.student-id-cards.index'));

        $this->assertDatabaseHas('student_id_cards', ['student_id' => $student->id]);
    }

    public function test_id_card_preview_renders(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $this->actingAs($admin)
            ->post(route('dashboard.student-id-cards.store'), [
                'student_id' => $student->id,
                'issue_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $card = \App\Models\StudentIdCard::where('student_id', $student->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard.student-id-cards.preview', $card))
            ->assertOk();
    }

    public function test_admit_card_batch_generation_creates_cards(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $exam = Exam::create([
            'name' => 'Midterm',
            'start_date' => now()->addWeek(),
            'total_marks' => 100,
        ]);

        $this->actingAs($admin)
            ->post(route('dashboard.admit-cards.batch.store'), [
                'exam_id' => $exam->id,
                'class_id' => $student->class_id,
                'issue_date' => now()->toDateString(),
                'details' => ['show_logo' => 1],
            ])
            ->assertRedirect(route('dashboard.admit-cards.index'));

        $this->assertDatabaseHas('admit_cards', ['student_id' => $student->id, 'exam_id' => $exam->id]);
    }

    public function test_admit_card_preview_renders(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $exam = Exam::create([
            'name' => 'Midterm',
            'start_date' => now()->addWeek(),
            'total_marks' => 100,
        ]);

        $this->actingAs($admin)
            ->post(route('dashboard.admit-cards.store'), [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'issue_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $card = \App\Models\AdmitCard::where('student_id', $student->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard.admit-cards.preview', $card))
            ->assertOk();
    }
}
