<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_sequential_invoice_numbers(): void
    {
        $first = Payment::generateInvoiceNumber();
        $this->assertStringStartsWith('INV'.date('Ymd'), $first);
        $this->assertEquals('INV'.date('Ymd').'0001', $first);
    }

    /** @test */
    public function it_increments_invoice_number_on_subsequent_calls(): void
    {
        Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'payment_method' => 'cash',
            'payment_status' => Payment::STATUS_PENDING,
        ]);

        $next = Payment::generateInvoiceNumber();
        $this->assertEquals('INV'.date('Ymd').'0002', $next);
    }

    /** @test */
    public function it_auto_generates_invoice_number_on_create(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'amount' => 500,
            'total_amount' => 500,
            'paid_amount' => 0,
            'due_amount' => 500,
            'payment_method' => 'cash',
            'payment_status' => Payment::STATUS_PENDING,
        ]);

        $this->assertNotNull($payment->invoice_number);
        $this->assertStringStartsWith('INV', $payment->invoice_number);
    }

    /** @test */
    public function it_returns_correct_status_label(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
        ]);

        $this->assertEquals('Completed', $payment->status_label);
    }

    /** @test */
    public function it_returns_unknown_label_for_invalid_status(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => 'invalid',
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
        ]);

        $this->assertEquals('Unknown', $payment->status_label);
    }

    /** @test */
    public function it_returns_correct_method_label(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => Payment::METHOD_BKASH,
            'amount' => 100,
            'total_amount' => 100,
        ]);

        $this->assertEquals('bKash', $payment->method_label);
    }

    /** @test */
    public function is_fully_paid_requires_completed_status_and_full_amount(): void
    {
        $completed = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 100,
            'due_amount' => 0,
        ]);
        $this->assertTrue($completed->is_fully_paid);

        $processing = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PROCESSING,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 100,
            'due_amount' => 0,
        ]);
        $this->assertFalse($processing->is_fully_paid);

        $partial = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 50,
            'due_amount' => 50,
        ]);
        $this->assertFalse($partial->is_fully_paid);
    }

    /** @test */
    public function is_overdue_checks_past_due_date_and_unpaid(): void
    {
        $overdue = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'due_date' => now()->subDays(5),
        ]);
        $this->assertTrue($overdue->is_overdue);

        $notOverdue = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'due_date' => now()->addDays(5),
        ]);
        $this->assertFalse($notOverdue->is_overdue);
    }

    /** @test */
    public function it_marks_payment_as_completed(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => 'cash',
            'amount' => 1000,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
        ]);

        $this->assertTrue($payment->markAsCompleted(['transaction_id' => 'TXN123']));
        $payment->refresh();

        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->payment_status);
        $this->assertEquals(1000, $payment->paid_amount);
        $this->assertEquals(0, $payment->due_amount);
        $this->assertNotNull($payment->payment_date);
    }

    /** @test */
    public function it_marks_payment_as_failed(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => 'cash',
            'amount' => 500,
            'total_amount' => 500,
        ]);

        $this->assertTrue($payment->markAsFailed('Insufficient funds'));
        $payment->refresh();

        $this->assertEquals(Payment::STATUS_FAILED, $payment->payment_status);
        $this->assertEquals('Insufficient funds', $payment->payment_details['failure_reason']);
    }

    /** @test */
    public function it_records_partial_payment(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => 'cash',
            'amount' => 1000,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
        ]);

        $this->assertTrue($payment->recordPayment(300));
        $payment->refresh();

        $this->assertEquals(300, $payment->paid_amount);
        $this->assertEquals(700, $payment->due_amount);
        $this->assertEquals(Payment::STATUS_PROCESSING, $payment->payment_status);
    }

    /** @test */
    public function it_completes_payment_when_full_amount_recorded(): void
    {
        $payment = Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => 'cash',
            'amount' => 1000,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
        ]);

        $payment->recordPayment(500);
        $payment->recordPayment(600);

        $payment->refresh();

        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->payment_status);
        $this->assertEquals(1000, $payment->paid_amount);
        $this->assertEquals(0, $payment->due_amount);
    }

    /** @test */
    public function scope_completed_filters_correctly(): void
    {
        Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
        ]);

        Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_method' => 'cash',
            'amount' => 100,
            'total_amount' => 100,
        ]);

        $completed = Payment::completed()->get();
        $this->assertCount(1, $completed);
    }

    /** @test */
    public function it_merges_gateway_config_with_defaults(): void
    {
        $payment = new Payment;

        $config = $payment->getGatewayConfig('nonexistent');

        $this->assertFalse($config['enabled']);
        $this->assertTrue($config['test_mode']);
        $this->assertEquals('BDT', $config['currency']);
        $this->assertEquals(0, $config['fee_percentage']);
    }
}
