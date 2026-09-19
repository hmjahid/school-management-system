<?php

namespace Tests\Unit\Models;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeePaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_sequential_invoice_numbers(): void
    {
        $first = FeePayment::generateInvoiceNumber();
        $this->assertStringStartsWith('INV-'.date('Ymd').'-', $first);
        $this->assertEquals('INV-'.date('Ymd').'-0001', $first);
    }

    #[Test]
    public function it_auto_generates_invoice_number_on_create(): void
    {
        $student = Student::factory()->create();
        $fee = Fee::create([
            'name' => 'Tuition Fee',
            'amount' => 500,
            'fee_type' => Fee::TYPE_TUITION,
            'frequency' => Fee::FREQUENCY_MONTHLY,
            'status' => Fee::STATUS_ACTIVE,
            'class_id' => SchoolClass::factory()->create()->id,
        ]);

        $feePayment = FeePayment::create([
            'student_id' => $student->id,
            'payment_date' => today(),
            'created_by' => User::factory()->create()->id,
            'fee_id' => $fee->id,
            'amount' => 500,
            'paid_amount' => 500,
            'balance' => 0,
            'payment_method' => FeePayment::METHOD_CASH,
            'status' => FeePayment::STATUS_PAID,
        ]);

        $this->assertNotNull($feePayment->invoice_number);
        $this->assertStringStartsWith('INV-', $feePayment->invoice_number);
    }

    #[Test]
    public function it_returns_formatted_amount(): void
    {
        $student = Student::factory()->create();
        $fee = Fee::create([
            'name' => 'Test Fee',
            'amount' => 1234.56,
            'fee_type' => Fee::TYPE_TUITION,
            'frequency' => Fee::FREQUENCY_MONTHLY,
            'status' => Fee::STATUS_ACTIVE,
            'class_id' => SchoolClass::factory()->create()->id,
        ]);

        $feePayment = FeePayment::create([
            'student_id' => $student->id,
            'payment_date' => today(),
            'created_by' => User::factory()->create()->id,
            'fee_id' => $fee->id,
            'amount' => 1234.56,
            'paid_amount' => 1000,
            'balance' => 234.56,
            'payment_method' => FeePayment::METHOD_CASH,
            'status' => FeePayment::STATUS_PARTIAL,
        ]);

        $this->assertEquals('1,234.56', $feePayment->formatted_amount);
        $this->assertEquals('1,000.00', $feePayment->formatted_paid_amount);
        $this->assertEquals('234.56', $feePayment->formatted_balance);
    }

    #[Test]
    public function it_returns_correct_status_badge(): void
    {
        $student = Student::factory()->create();
        $fee = Fee::create([
            'name' => 'Test Fee',
            'amount' => 500,
            'fee_type' => Fee::TYPE_TUITION,
            'frequency' => Fee::FREQUENCY_MONTHLY,
            'status' => Fee::STATUS_ACTIVE,
            'class_id' => SchoolClass::factory()->create()->id,
        ]);

        $paid = FeePayment::create([
            'student_id' => $student->id,
            'payment_date' => today(),
            'created_by' => User::factory()->create()->id,
            'fee_id' => $fee->id,
            'amount' => 500,
            'paid_amount' => 500,
            'balance' => 0,
            'payment_method' => FeePayment::METHOD_CASH,
            'status' => FeePayment::STATUS_PAID,
        ]);
        $this->assertStringContainsString('badge bg-success', $paid->status_badge);
        $this->assertStringContainsString('Paid', $paid->status_badge);

        $pending = FeePayment::create([
            'student_id' => Student::factory()->create()->id,
            'payment_date' => today(),
            'created_by' => User::factory()->create()->id,
            'fee_id' => $fee->id,
            'amount' => 500,
            'paid_amount' => 0,
            'balance' => 500,
            'payment_method' => FeePayment::METHOD_CASH,
            'status' => FeePayment::STATUS_PENDING,
        ]);
        $this->assertStringContainsString('badge bg-warning', $pending->status_badge);
    }

    #[Test]
    public function it_returns_correct_payment_method_label(): void
    {
        $student = Student::factory()->create();
        $fee = Fee::create([
            'name' => 'Test Fee',
            'amount' => 500,
            'fee_type' => Fee::TYPE_TUITION,
            'frequency' => Fee::FREQUENCY_MONTHLY,
            'status' => Fee::STATUS_ACTIVE,
            'class_id' => SchoolClass::factory()->create()->id,
        ]);

        $cash = FeePayment::create([
            'student_id' => $student->id,
            'payment_date' => today(),
            'created_by' => User::factory()->create()->id,
            'fee_id' => $fee->id,
            'amount' => 500,
            'paid_amount' => 500,
            'balance' => 0,
            'payment_method' => FeePayment::METHOD_CASH,
            'status' => FeePayment::STATUS_PAID,
        ]);
        $this->assertEquals('Cash', $cash->payment_method_label);

        $online = FeePayment::create([
            'student_id' => Student::factory()->create()->id,
            'payment_date' => today(),
            'created_by' => User::factory()->create()->id,
            'fee_id' => $fee->id,
            'amount' => 500,
            'paid_amount' => 500,
            'balance' => 0,
            'payment_method' => FeePayment::METHOD_ONLINE_PAYMENT,
            'status' => FeePayment::STATUS_PAID,
        ]);
        $this->assertEquals('Online Payment', $online->payment_method_label);
    }

    #[Test]
    public function it_returns_fallback_label_for_unknown_method(): void
    {
        $student = Student::factory()->create();
        $fee = Fee::create([
            'name' => 'Test Fee',
            'amount' => 500,
            'fee_type' => Fee::TYPE_TUITION,
            'frequency' => Fee::FREQUENCY_MONTHLY,
            'status' => Fee::STATUS_ACTIVE,
            'class_id' => SchoolClass::factory()->create()->id,
        ]);

        $feePayment = FeePayment::create([
            'student_id' => $student->id,
            'payment_date' => today(),
            'created_by' => User::factory()->create()->id,
            'fee_id' => $fee->id,
            'amount' => 500,
            'paid_amount' => 500,
            'balance' => 0,
            'payment_method' => 'crypto_currency',
            'status' => FeePayment::STATUS_PAID,
        ]);

        $this->assertEquals('Crypto currency', $feePayment->payment_method_label);
    }

    #[Test]
    public function it_returns_statuses_and_methods_arrays(): void
    {
        $statuses = FeePayment::getStatuses();
        $this->assertArrayHasKey(FeePayment::STATUS_PAID, $statuses);
        $this->assertArrayHasKey(FeePayment::STATUS_PENDING, $statuses);

        $methods = FeePayment::getPaymentMethods();
        $this->assertArrayHasKey(FeePayment::METHOD_CASH, $methods);
        $this->assertArrayHasKey(FeePayment::METHOD_MOBILE_BANKING, $methods);
    }
}
