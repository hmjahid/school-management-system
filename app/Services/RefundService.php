<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    /**
     * The payment service instance.
     *
     * @var \App\Services\PaymentService
     */
    protected $paymentService;

    /**
     * Create a new service instance.
     *
     * @param  \App\Services\PaymentService  $paymentService
     * @return void
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initiate a refund for a payment.
     *
     * @param  \App\Models\Payment  $payment
     * @param  float  $amount
     * @param  string  $reason
     * @param  \App\Models\User  $processedBy
     * @param  array  $metadata
     * @return array
     */
    public function initiateRefund(
        Payment $payment,
        float $amount,
        string $reason,
        User $processedBy,
        array $metadata = []
    ): array {
        // Validate refund amount
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Refund amount must be greater than zero',
                'code' => 'invalid_amount',
            ];
        }

        // Check if payment is refundable
        if (!$this->isRefundable($payment)) {
            return [
                'success' => false,
                'message' => 'This payment is not eligible for a refund',
                'code' => 'not_refundable',
            ];
        }

        // Check available amount for refund
        $refundableAmount = $this->getRefundableAmount($payment);
        if ($amount > $refundableAmount) {
            return [
                'success' => false,
                'message' => "Maximum refundable amount is {$refundableAmount}",
                'code' => 'amount_exceeds_limit',
                'max_amount' => $refundableAmount,
            ];
        }

        // Create refund record (persisted even if the gateway later fails,
        // so a failed refund can be audited).
        $refund = Refund::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->created_by,
            'processed_by' => $processedBy->id,
            'amount' => $amount,
            'currency' => $payment->currency ?? 'BDT',
            'reason' => $reason,
            'status' => 'pending',
            'metadata' => array_merge($metadata, [
                'original_payment' => [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'transaction_id' => $payment->transaction_id,
                ],
            ]),
        ]);

        // Process refund with payment gateway
        $gatewayResponse = $this->processGatewayRefund($payment, $amount, $reason);

        if (!$gatewayResponse['success']) {
            $refund->update([
                'status' => 'failed',
                'transaction_id' => $gatewayResponse['transaction_id'] ?? null,
                'metadata' => array_merge($refund->metadata ?? [], [
                    'error' => $gatewayResponse['message'] ?? 'Gateway refund failed',
                    'gateway_response' => $gatewayResponse,
                ]),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process refund: ' . ($gatewayResponse['message'] ?? 'Gateway refund failed'),
                'code' => 'refund_failed',
                'refund' => $refund,
            ];
        }

        // Update refund status
        $refund->update([
            'status' => 'completed',
            'transaction_id' => $gatewayResponse['transaction_id'] ?? null,
            'processed_at' => now(),
            'metadata' => array_merge($refund->metadata ?? [], [
                'gateway_response' => $gatewayResponse,
            ]),
        ]);

        // Update payment status if fully refunded
        $this->updatePaymentRefundStatus($payment, $amount);

        return [
            'success' => true,
            'message' => 'Refund processed successfully',
            'refund' => $refund,
        ];
    }

    /**
     * Check if a payment is eligible for a refund.
     *
     * @param  \App\Models\Payment  $payment
     * @return bool
     */
    public function isRefundable(Payment $payment): bool
    {
        // Only completed payments can be refunded
        if ($payment->payment_status !== Payment::STATUS_COMPLETED) {
            return false;
        }

        // Check if payment method supports refunds
        if (!$this->paymentService->supportsRefunds($payment->payment_method)) {
            return false;
        }

        // Check if payment is not already fully refunded
        $refundedAmount = $payment->refunds()
            ->whereIn('status', ['completed', 'pending'])
            ->sum('amount');

        return $payment->amount > $refundedAmount;
    }

    /**
     * Get the maximum refundable amount for a payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return float
     */
    public function getRefundableAmount(Payment $payment): float
    {
        if (!$this->isRefundable($payment)) {
            return 0;
        }

        $refundedAmount = $payment->refunds()
            ->whereIn('status', ['completed', 'pending'])
            ->sum('amount');

        return max(0, $payment->amount - $refundedAmount);
    }

    /**
     * Process refund with the payment gateway.
     *
     * @param  \App\Models\Payment  $payment
     * @param  float  $amount
     * @param  string  $reason
     * @return array
     */
    protected function processGatewayRefund(Payment $payment, float $amount, string $reason): array
    {
        try {
            // Process refund with the payment gateway
            $gatewayResponse = $this->paymentService->processRefund(
                $payment->payment_method,
                $payment->transaction_id,
                $amount,
                $reason,
                $payment->payment_details ?? []
            );

            if (! ($gatewayResponse['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $gatewayResponse['message'] ?? 'Gateway refund failed',
                    'code' => 'gateway_error',
                ];
            }

            return [
                'success' => true,
                'transaction_id' => $gatewayResponse['transaction_id'] ?? null,
                'gateway_response' => $gatewayResponse,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'gateway_error',
            ];
        }
    }

    /**
     * Update payment refund status based on refund amount.
     *
     * @param  \App\Models\Payment  $payment
     * @param  float  $refundAmount
     * @return void
     */
    protected function updatePaymentRefundStatus(Payment $payment, float $refundAmount): void
    {
        $totalRefunded = $payment->refunds()
            ->whereIn('status', ['completed', 'pending'])
            ->sum('amount');

        // Update payment status based on refund amount
        if ($totalRefunded >= $payment->amount) {
            $payment->update(['refund_status' => 'fully_refunded']);
        } elseif ($totalRefunded > 0) {
            $payment->update(['refund_status' => 'partially_refunded']);
        }
    }

    /**
     * Get refund details by ID.
     *
     * @param  string  $refundId
     * @return \App\Models\Refund|null
     */
    public function getRefund(string $refundId): ?Refund
    {
        return Refund::with(['payment', 'user', 'processor'])->find($refundId);
    }

    /**
     * List refunds with optional filters.
     *
     * @param  array  $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function listRefunds(array $filters = [])
    {
        $query = Refund::with(['payment', 'user', 'processor'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_id'])) {
            $query->where('payment_id', $filters['payment_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Cancel a pending refund.
     *
     * @param  \App\Models\Refund  $refund
     * @param  string  $reason
     * @return bool
     */
    public function cancelRefund(Refund $refund, string $reason): bool
    {
        if ($refund->status !== 'pending') {
            throw new \InvalidArgumentException('Only pending refunds can be cancelled');
        }

        $refund->update([
            'status' => 'cancelled',
            'metadata' => array_merge($refund->metadata ?? [], [
                'cancellation_reason' => $reason,
                'cancelled_at' => now()->toDateTimeString(),
            ]),
        ]);

        // Trigger refund cancelled event
        // Event::dispatch(new RefundCancelled($refund));

        return true;
    }
}
