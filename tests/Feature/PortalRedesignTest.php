<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_student_portal_loads_with_routine_tab(): void
    {
        $class = SchoolClass::create(['name' => 'Class 1']);
        $user = User::factory()->create();
        $user->assignRole('student');

        Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender' => 'male',
            'admission_no' => 'A001',
            'admission_number' => 'A001',
            'roll_no' => 'R001',
            'roll_number' => 'R001',
            'admission_date' => now()->toDateString(),
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'address' => 'Test',
            'phone' => '01',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('portal'))
            ->assertOk()
            ->assertSee(site_ui('portal.routine'));
    }

    public function test_parent_portal_loads_with_dues_and_calendar_tabs(): void
    {
        $class = SchoolClass::create(['name' => 'Class 2']);
        $parent = User::factory()->create();
        $parent->assignRole('parent');

        $guardian = Guardian::create(['user_id' => $parent->id]);

        $student = Student::create([
            'user_id' => User::factory()->create()->id,
            'class_id' => $class->id,
            'first_name' => 'Child',
            'last_name' => 'One',
            'gender' => 'male',
            'admission_no' => 'A002',
            'admission_number' => 'A002',
            'roll_no' => 'R002',
            'roll_number' => 'R002',
            'admission_date' => now()->toDateString(),
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'address' => 'Test',
            'phone' => '02',
            'status' => 'active',
        ]);

        $guardian->students()->attach($student->id);

        $this->actingAs($parent)
            ->get(route('portal'))
            ->assertOk()
            ->assertSee(site_ui('portal.dues_timeline'))
            ->assertSee(site_ui('portal.attendance_calendar'));
    }

    public function test_student_can_message_teacher(): void
    {
        $class = SchoolClass::create(['name' => 'Class 3']);
        $user = User::factory()->create();
        $user->assignRole('student');

        Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender' => 'male',
            'admission_no' => 'A003',
            'admission_number' => 'A003',
            'roll_no' => 'R003',
            'roll_number' => 'R003',
            'admission_date' => now()->toDateString(),
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'address' => 'Test',
            'phone' => '03',
            'status' => 'active',
        ]);

        $teacherUser = User::factory()->create();
        $teacher = Teacher::create(['user_id' => $teacherUser->id]);

        $this->actingAs($user)
            ->post(route('portal.message'), [
                'teacher_id' => $teacher->id,
                'subject' => 'Help',
                'body' => 'I need help with math.',
            ])
            ->assertRedirect(route('portal'));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $user->id,
            'receiver_id' => $teacherUser->id,
            'subject' => 'Help',
        ]);
    }
}
