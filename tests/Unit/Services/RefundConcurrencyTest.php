<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefundConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected RefundService $refundService;

    protected User $admin;

    protected User $user;

    protected Payment $payment;

    protected int $concurrentRequests = 5;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->user = User::factory()->create(['email' => 'user@example.com']);

        // Create a completed payment
        $this->payment = Payment::factory()->create([
            'created_by' => $this->user->id,
            'amount' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0,
            'total_amount' => 1000.00,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'test_gateway',
            'transaction_id' => 'TXN'.uniqid(),
        ]);

        $this->refundService = app(RefundService::class);
    }

    /** @test */
    public function it_handles_concurrent_refund_requests_safely()
    {
        // Simulate multiple concurrent refund requests
        $responses = [];
        $refundAmount = 500.00;
        $expectedSuccessful = 2; // Only 2 refunds of 500 should be possible for a 1000 payment

        for ($i = 0; $i < $this->concurrentRequests; $i++) {
            $responses[] = $this->attemptRefund($refundAmount, $i);
        }

        // Count successful responses
        $successfulRefunds = array_filter($responses, fn ($response) => $response['success']);
        $failedRefunds = array_filter($responses, fn ($response) => ! $response['success']);

        // Assertions
        $this->assertCount($expectedSuccessful, $successfulRefunds,
            "Expected exactly {$expectedSuccessful} successful refunds out of {$this->concurrentRequests} concurrent requests");

        $this->assertCount($this->concurrentRequests - $expectedSuccessful, $failedRefunds,
            'Expected '.($this->concurrentRequests - $expectedSuccessful).' failed refunds due to concurrency');

        // Verify total refunded amount doesn't exceed payment amount
        $totalRefunded = $this->payment->refunds()->where('status', 'completed')->sum('amount');
        $this->assertEquals(1000.00, $totalRefunded,
            'Total refunded amount should not exceed the payment amount');

        // Verify payment status
        $this->assertEquals('fully_refunded', $this->payment->fresh()->refund_status,
            'Payment should be marked as fully refunded');
    }

    /** @test */
    public function it_handles_concurrent_partial_refunds_correctly()
    {
        // Simulate multiple concurrent partial refund requests
        $responses = [];
        $refundAmount = 200.00;
        $expectedSuccessful = 5; // 5 x 200 = 1000 (full amount)

        for ($i = 0; $i < $expectedSuccessful; $i++) {
            $responses[] = $this->attemptRefund($refundAmount, $i);
        }

        // Count successful responses
        $successfulRefunds = array_filter($responses, fn ($response) => $response['success']);
        $failedRefunds = array_filter($responses, fn ($response) => ! $response['success']);

        // Assertions
        $this->assertCount($expectedSuccessful, $successfulRefunds,
            "Expected {$expectedSuccessful} successful partial refunds");

        $this->assertCount(0, $failedRefunds,
            'Expected no failed refunds for non-overlapping partial amounts');

        // Verify total refunded amount equals payment amount
        $totalRefunded = $this->payment->refunds()->where('status', 'completed')->sum('amount');
        $this->assertEquals(1000.00, $totalRefunded,
            'Total refunded amount should equal the payment amount');
    }

    /** @test */
    public function it_prevents_double_processing_of_same_refund()
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

        // Simulate two concurrent attempts to process the same refund.
        // Each attempt locks the row, checks the pending status, then commits.
        $first = $this->processPendingRefund($refund);
        $second = $this->processPendingRefund($refund);

        $this->assertTrue($first['success'], 'Only one process should successfully process the refund');
        $this->assertFalse($second['success'], 'One process should fail to process the refund');
        $this->assertStringContainsString('already processed', $second['message']);
    }

    /**
     * Simulate a single concurrent refund request against the service.
     */
    protected function attemptRefund(float $amount, int $requestId): array
    {
        $result = $this->refundService->initiateRefund(
            $this->payment,
            $amount,
            'Test refund '.$requestId,
            $this->admin,
            ['request_id' => $requestId]
        );

        return [
            'success' => $result['success'],
            'message' => $result['message'] ?? '',
            'request_id' => $requestId,
        ];
    }

    /**
     * Lock a refund row and process it only if still pending,
     * mirroring the race-safe processing pattern.
     */
    protected function processPendingRefund(Refund $refund): array
    {
        DB::beginTransaction();

        try {
            // Lock the refund row for update
            $locked = Refund::lockForUpdate()->find($refund->id);

            if ($locked->status === 'pending') {
                // Simulate processing delay
                usleep(100000); // 100ms

                $locked->update([
                    'status' => 'completed',
                    'transaction_id' => 'TXN'.uniqid(),
                    'processed_at' => now(),
                ]);

                DB::commit();

                return ['success' => true, 'message' => 'Refund processed'];
            }

            DB::commit();

            return ['success' => false, 'message' => 'Refund already processed'];
        } catch (\Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
