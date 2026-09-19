<?php

namespace Tests\Unit\Services;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\Payment\BkashGatewayAdapter;
use Database\Factories\SchoolClassFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class PaymentSideEffectsTest extends TestCase
{
    use RefreshDatabase;

    private function feePayment(array $attributes = []): FeePayment
    {
        $student = Student::factory()->create();

        $fee = Fee::create([
            'name' => 'Test Fee',
            'code' => 'FEE'.uniqid(),
            'class_id' => SchoolClassFactory::new()->create()->id,
            'amount' => 1000.00,
            'fee_type' => 'tuition',
            'created_by' => User::factory()->create()->id,
        ]);

        return FeePayment::create(array_merge([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => 1000.00,
            'paid_amount' => 0,
            'balance' => 1000.00,
            'status' => FeePayment::STATUS_PENDING,
            'payment_method' => FeePayment::METHOD_CASH,
            'payment_date' => now()->toDateString(),
            'created_by' => User::factory()->create()->id,
        ], $attributes));
    }

    private function paymentWithFee(int $feePaymentId): Payment
    {
        // The side-effect logic only reads from the payment (never persists it),
        // so a non-persisted model carrying metadata is sufficient.
        $payment = new Payment([
            'invoice_number' => 'INV'.uniqid(),
            'amount' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'due_amount' => 1000.00,
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'currency' => 'BDT',
            'metadata' => ['fee_payment_id' => $feePaymentId],
        ]);
        $payment->id = 1;

        return $payment;
    }

    private function invokeSideEffects(Payment $payment, array $context): void
    {
        $adapter = new BkashGatewayAdapter;
        $method = new ReflectionMethod($adapter, 'applyPaymentSideEffects');
        $method->setAccessible(true);
        $method->invoke($adapter, $payment, $context);
    }

    #[Test]
    public function it_marks_linked_fee_payment_as_paid(): void
    {
        $fee = $this->feePayment();

        $payment = $this->paymentWithFee($fee->id);
        $this->invokeSideEffects($payment, [
            'gateway' => 'bkash',
            'transaction_id' => 'TRX123',
        ]);

        $fee->refresh();
        $this->assertEquals(FeePayment::STATUS_PAID, $fee->status);
        $this->assertEquals(1000.00, $fee->paid_amount);
        $this->assertEquals(0, $fee->balance);
        $this->assertEquals(FeePayment::METHOD_ONLINE_PAYMENT, $fee->payment_method);
        $this->assertEquals('TRX123', $fee->transaction_id);
        $this->assertEquals('bkash', $fee->metadata['gateway']);
    }

    #[Test]
    public function it_does_nothing_without_fee_payment_id(): void
    {
        $payment = new Payment([
            'invoice_number' => 'INV'.uniqid(),
            'amount' => 1000.00,
            'total_amount' => 1000.00,
            'payment_status' => Payment::STATUS_PENDING,
            'currency' => 'BDT',
            'metadata' => [],
        ]);
        $payment->id = 1;

        $this->invokeSideEffects($payment, ['gateway' => 'bkash']);

        // The in-memory payment is never mutated by the side-effect logic.
        $this->assertEquals(Payment::STATUS_PENDING, $payment->payment_status);
    }

    #[Test]
    public function it_does_not_overwrite_already_paid_fee_payment(): void
    {
        $fee = $this->feePayment([
            'paid_amount' => 1000.00,
            'balance' => 0,
            'status' => FeePayment::STATUS_PAID,
            'payment_method' => FeePayment::METHOD_CASH,
            'transaction_id' => 'ORIG',
        ]);

        $payment = $this->paymentWithFee($fee->id);
        $this->invokeSideEffects($payment, [
            'gateway' => 'bkash',
            'transaction_id' => 'NEWTXN',
        ]);

        $fee->refresh();
        $this->assertEquals('ORIG', $fee->transaction_id);
        $this->assertEquals(FeePayment::METHOD_CASH, $fee->payment_method);
    }
}
