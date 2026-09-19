<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\Batch;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardKpiCardsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        foreach (['admin', 'teacher', 'parent'] as $name) {
            Role::findOrCreate($name);
        }
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createStudent(User $user): Student
    {
        $class = SchoolClass::create(['name' => 'Class One']);

        return Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'admission_number' => 'ADM'.$user->id,
            'admission_date' => now()->toDateString(),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
    }

    public function test_dashboard_home_loads_and_links_kpi_cards_to_module_pages(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee(route('dashboard.students'), false)
            ->assertSee(route('dashboard.teachers'), false)
            ->assertSee(route('dashboard.parents'), false)
            ->assertSee(route('dashboard.attendance'), false)
            ->assertSee(route('dashboard.fee-payments.index'), false);
    }

    public function test_dashboard_home_shows_pending_count_badges(): void
    {
        $user = $this->admin();

        $session = AcademicSession::factory()->create();
        $batch = Batch::create(['name' => 'Batch One']);
        $student = $this->createStudent($user);
        $fee = Fee::create([
            'name' => 'Tuition Fee',
            'amount' => 100,
            'fee_type' => 'tuition',
        ]);

        Admission::create([
            'application_number' => 'APP-1001',
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
            'first_name' => 'Pending',
            'last_name' => 'Admission',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(8)->toDateString(),
            'email' => 'pending@example.com',
            'phone' => '01700000000',
            'address' => 'Test Address',
            'city' => 'Dhaka',
            'postal_code' => '1200',
            'father_name' => 'Father',
            'father_phone' => '01700000001',
            'mother_name' => 'Mother',
            'mother_phone' => '01700000002',
            'status' => Admission::STATUS_SUBMITTED,
        ]);

        FeePayment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => 100,
            'paid_amount' => 0,
            'balance' => 100,
            'payment_date' => now(),
            'payment_method' => FeePayment::METHOD_CASH,
            'status' => FeePayment::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('pending admissions', false)
            ->assertSee('pending dues', false);
    }
}
