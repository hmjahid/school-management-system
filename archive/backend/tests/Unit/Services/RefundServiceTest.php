<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RefundService $refundService;
    protected PaymentService $paymentService;
    protected User $admin;
    protected User $user;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->user = User::factory()->create(['email' => 'user@example.com']);

        // Create a completed payment
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'currency' => 'BDT',
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'test_gateway',
            'transaction_id' => 'TXN' . uniqid(),
        ]);

        // Mock the PaymentService
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->refundService = new RefundService($this->paymentService);
    }

    /** @test */
    public function it_can_initiate_a_refund()
    {
        // Mock the payment service to simulate a successful refund
        $this->paymentService->method('processRefund')
            ->willReturn([
                'success' => true,
                'transaction_id' => 'R-' . uniqid(),
            ]);

        $result = $this->refundService->initiateRefund(
            $this->payment,
            500.00,
            'Customer requested partial refund',
            $this->admin
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Refund processed successfully', $result['message']);
        $this->assertInstanceOf(Refund::class, $result['refund']);
        $this->assertEquals(500.00, $result['refund']->amount);
        $this->assertEquals('completed', $result['refund']->status);
    }

    /** @test */
    public function it_prevents_refund_for_non_refundable_payment()
    {
        // Create a failed payment
        $failedPayment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'payment_status' => Payment::STATUS_FAILED,
        ]);

        $result = $this->refundService->initiateRefund(
            $failedPayment,
            500.00,
            'Test refund',
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('This payment is not eligible for a refund', $result['message']);
    }

    /** @test */
    public function it_prevents_refund_amount_exceeding_available_balance()
    {
        $result = $this->refundService->initiateRefund(
            $this->payment,
            1500.00, // More than the payment amount
            'Test refund',
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Maximum refundable amount is', $result['message']);
    }

    /** @test */
    public function it_handles_refund_failures_gracefully()
    {
        // Mock the payment service to simulate a failed refund
        $this->paymentService->method('processRefund')
            ->willReturn([
                'success' => false,
                'message' => 'Insufficient funds',
                'code' => 'insufficient_funds',
            ]);

        $result = $this->refundService->initiateRefund(
            $this->payment,
            500.00,
            'Test refund',
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to process refund: Insufficient funds', $result['message']);
        
        // Verify the refund was created with failed status
        $refund = Refund::where('payment_id', $this->payment->id)->first();
        $this->assertNotNull($refund);
        $this->assertEquals('failed', $refund->status);
    }

    /** @test */
    public function it_calculates_refundable_amount_correctly()
    {
        // Initial refund
        $this->refundService->initiateRefund(
            $this->payment,
            300.00,
            'Partial refund',
            $this->admin
        );

        // Check remaining refundable amount
        $refundableAmount = $this->refundService->getRefundableAmount($this->payment);
        $this->assertEquals(700.00, $refundableAmount);

        // Another refund
        $this->refundService->initiateRefund(
            $this->payment,
            500.00,
            'Second partial refund',
            $this->admin
        );

        // Check remaining refundable amount
        $refundableAmount = $this->refundService->getRefundableAmount($this->payment);
        $this->assertEquals(200.00, $refundableAmount);

        // Final refund for remaining amount
        $this->refundService->initiateRefund(
            $this->payment,
            200.00,
            'Final refund',
            $this->admin
        );

        // No more refunds should be possible
        $refundableAmount = $this->refundService->getRefundableAmount($this->payment);
        $this->assertEquals(0.00, $refundableAmount);
    }

    /** @test */
    public function it_cancels_a_pending_refund()
    {
        // Create a pending refund
        $refund = Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 500.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Test refund',
        ]);

        $result = $this->refundService->cancelRefund($refund, 'Customer changed mind');

        $this->assertTrue($result);
        $refund->refresh();
        $this->assertEquals('cancelled', $refund->status);
        $this->assertEquals('Customer changed mind', $refund->getMeta('cancellation_reason'));
    }

    /** @test */
    public function it_lists_refunds_with_filters()
    {
        // Create test refunds
        Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 300.00,
            'currency' => 'BDT',
            'status' => 'completed',
            'reason' => 'Test refund 1',
            'created_at' => now()->subDays(5),
        ]);

        Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 200.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Test refund 2',
            'created_at' => now()->subDays(2),
        ]);

        // Test filtering by status
        $completedRefunds = $this->refundService->listRefunds(['status' => 'completed']);
        $this->assertCount(1, $completedRefunds);
        $this->assertEquals(300.00, $completedRefunds->first()->amount);

        // Test date range filter
        $recentRefunds = $this->refundService->listRefunds([
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);
        $this->assertCount(1, $recentRefunds);
        $this->assertEquals(200.00, $recentRefunds->first()->amount);
    }

    /** @test */
    public function it_prevents_double_refunding()
    {
        // First refund
        $this->refundService->initiateRefund(
            $this->payment,
            1000.00,
            'Full refund',
            $this->admin
        );

        // Try to refund again
        $result = $this->refundService->initiateRefund(
            $this->payment,
            100.00,
            'Additional refund',
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('This payment is not eligible for a refund', $result['message']);
    }

    /** @test */
    public function it_handles_database_rollback_on_failure()
    {
        // Simulate a database failure during refund processing
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        
        // Mock the payment service to throw an exception
        $this->paymentService->method('processRefund')
            ->willThrowException(new \Exception('Database connection failed'));

        $result = $this->refundService->initiateRefund(
            $this->payment,
            500.00,
            'Test refund',
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to process refund', $result['message']);
    }
}
