<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    protected function makeRefund(Payment $payment, array $overrides = []): Refund
    {
        return Refund::create(array_merge([
            'payment_id' => $payment->id,
            'user_id' => User::factory()->create()->id,
            'processed_by' => User::factory()->create()->id,
            'amount' => 500,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Test refund',
        ], $overrides));
    }

    protected function makePayment(): Payment
    {
        return Payment::create([
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory()->create()->id,
            'amount' => 1000,
            'total_amount' => 1000,
            'payment_method' => 'cash',
            'payment_status' => Payment::STATUS_COMPLETED,
        ]);
    }

    /** @test */
    public function it_returns_formatted_amount_with_currency(): void
    {
        $payment = $this->makePayment();
        $refund = $this->makeRefund($payment, ['amount' => 500]);

        $this->assertEquals('500.00 BDT', $refund->formatted_amount);
    }

    /** @test */
    public function it_returns_correct_status_label(): void
    {
        $payment = $this->makePayment();

        $pending = $this->makeRefund($payment, ['status' => 'pending']);
        $this->assertEquals('Pending', $pending->status_label);

        $completed = $this->makeRefund($payment, ['status' => 'completed', 'amount' => 300]);
        $this->assertEquals('Completed', $completed->status_label);
    }

    /** @test */
    public function it_returns_ucfirst_for_unknown_status(): void
    {
        $payment = $this->makePayment();
        $refund = $this->makeRefund($payment, ['status' => 'processing']);

        $this->assertEquals('Processing', $refund->status_label);
    }

    /** @test */
    public function it_marks_refund_as_completed(): void
    {
        $payment = $this->makePayment();
        $refund = $this->makeRefund($payment);

        $this->assertTrue($refund->markAsCompleted('TXN-REFUND-001'));
        $refund->refresh();

        $this->assertEquals('completed', $refund->status);
        $this->assertEquals('TXN-REFUND-001', $refund->transaction_id);
        $this->assertNotNull($refund->processed_at);
    }

    /** @test */
    public function mark_as_completed_preserves_existing_transaction_id(): void
    {
        $payment = $this->makePayment();
        $refund = $this->makeRefund($payment, ['transaction_id' => 'EXISTING-TXN']);

        $refund->markAsCompleted();
        $refund->refresh();

        $this->assertEquals('EXISTING-TXN', $refund->transaction_id);
    }

    /** @test */
    public function it_marks_refund_as_failed(): void
    {
        $payment = $this->makePayment();
        $refund = $this->makeRefund($payment);

        $this->assertTrue($refund->markAsFailed('Gateway error'));
        $refund->refresh();

        $this->assertEquals('failed', $refund->status);
        $this->assertEquals('Gateway error', $refund->getMeta('error'));
        $this->assertNotNull($refund->getMeta('failed_at'));
    }

    /** @test */
    public function it_checks_pending_status(): void
    {
        $payment = $this->makePayment();
        $pending = $this->makeRefund($payment);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isCompleted());
        $this->assertFalse($pending->isFailed());
    }

    /** @test */
    public function it_retrieves_meta_values(): void
    {
        $payment = $this->makePayment();
        $refund = $this->makeRefund($payment, [
            'metadata' => ['cancellation_reason' => 'Customer request', 'priority' => 'high'],
        ]);

        $this->assertEquals('Customer request', $refund->getMeta('cancellation_reason'));
        $this->assertEquals('high', $refund->getMeta('priority'));
        $this->assertNull($refund->getMeta('nonexistent'));
        $this->assertEquals('default', $refund->getMeta('nonexistent', 'default'));
    }

    /** @test */
    public function scope_completed_filters_correctly(): void
    {
        $payment = $this->makePayment();

        $this->makeRefund($payment, ['status' => 'completed', 'amount' => 300]);
        $this->makeRefund($payment, ['status' => 'pending', 'amount' => 200]);

        $completed = Refund::completed()->get();
        $this->assertCount(1, $completed);
        $this->assertEquals(300, $completed->first()->amount);
    }
}
