<?php

namespace Tests\Feature;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentMethodFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createStudent(): Student
    {
        $class = SchoolClass::create(['name' => 'Class One']);

        return Student::create([
            'user_id' => User::factory()->create()->id,
            'class_id' => $class->id,
            'admission_number' => 'ADM'.uniqid(),
            'admission_date' => now()->toDateString(),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
    }

    private function payment(int $createdBy, string $method, array $metadata = []): FeePayment
    {
        $student = $this->createStudent();
        $fee = Fee::create([
            'name' => 'Tuition Fee',
            'amount' => 100,
            'fee_type' => 'tuition',
        ]);

        return FeePayment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => 100,
            'paid_amount' => 100,
            'balance' => 0,
            'payment_date' => now(),
            'payment_method' => $method,
            'status' => FeePayment::STATUS_PAID,
            'metadata' => $metadata,
            'created_by' => $createdBy,
        ]);
    }

    public function test_index_filters_payments_by_offline_method(): void
    {
        $admin = $this->admin();
        $cash = $this->payment($admin->id, FeePayment::METHOD_CASH);
        $online = $this->payment($admin->id, FeePayment::METHOD_ONLINE_PAYMENT, ['gateway' => 'bkash']);

        $this->actingAs($admin)
            ->get(route('dashboard.fee-payments.index', ['method' => 'cash']))
            ->assertStatus(200)
            ->assertSee($cash->invoice_number, false)
            ->assertDontSee($online->invoice_number, false);
    }

    public function test_index_filters_payments_by_online_gateway(): void
    {
        $admin = $this->admin();
        $cash = $this->payment($admin->id, FeePayment::METHOD_CASH);
        $bkash = $this->payment($admin->id, FeePayment::METHOD_ONLINE_PAYMENT, ['gateway' => 'bkash']);

        $this->actingAs($admin)
            ->get(route('dashboard.fee-payments.index', ['method' => 'bkash']))
            ->assertStatus(200)
            ->assertSee($bkash->invoice_number, false)
            ->assertDontSee($cash->invoice_number, false);
    }

    public function test_index_shows_all_payments_without_method_filter(): void
    {
        $admin = $this->admin();
        $cash = $this->payment($admin->id, FeePayment::METHOD_CASH);
        $nagad = $this->payment($admin->id, FeePayment::METHOD_ONLINE_PAYMENT, ['gateway' => 'nagad']);

        $this->actingAs($admin)
            ->get(route('dashboard.fee-payments.index'))
            ->assertStatus(200)
            ->assertSee($cash->invoice_number, false)
            ->assertSee($nagad->invoice_number, false);
    }
}
