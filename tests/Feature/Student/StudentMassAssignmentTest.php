<?php

namespace Tests\Feature\Student;

use App\Http\Controllers\StudentController;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StudentMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->adminRoleId = User::roleIdFor('admin');
        $this->studentRoleId = User::roleIdFor('student');
    }

    /** @test */
    public function student_store_ignores_privileged_role_id_and_user_id()
    {
        $class = SchoolClass::factory()->create();

        $this->actingAs($this->admin);

        $request = Request::create('/dashboard/students', 'POST', [
            'name' => 'Jane Doe',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'class_id' => $class->id,
            'admission_number' => 'ADM-'.uniqid(),
            'admission_date' => now()->toDateString(),
            'present_address' => 'Somewhere',
            'status' => 'active',
            // Attacker attempts privilege escalation / ownership takeover:
            'role_id' => $this->adminRoleId,
            'user_id' => 999,
            'is_admin' => 1,
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new StudentController();
        $controller->store($request);

        $student = Student::query()->orderByDesc('id')->first();
        $this->assertNotNull($student);

        $user = $student->user;

        // The account role must remain "student", never escalated to admin.
        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertSame($this->studentRoleId, $user->role_id);

        // A supplied user_id must not reassign ownership of the student record.
        $this->assertNotSame(999, $student->user_id);
        $this->assertSame($user->id, $student->user_id);
    }
}
