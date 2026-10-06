<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

/**
 * Credential-free test/sandbox gateway.
 *
 * initialize() only redirects to the local sandbox page (route
 * `payments.sandbox`) — no external host is ever contacted. The page POSTs
 * `simulate=success|failure|cancel` back to the product, which is handled by
 * processCallback(); a successful simulation marks the payment paid with the
 * reserved transaction id `TEST-<reference>` and runs the same side effects
 * every other gateway runs on completion.
 */
class TestGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;

    public const SIMULATE_SUCCESS = 'success';

    public const SIMULATE_FAILURE = 'failure';

    public const SIMULATE_CANCEL = 'cancel';

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $reference = (string) $payment->invoice_number;
        $redirectUrl = route('payments.sandbox', ['payment' => $payment->id]);

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'gateway_reference' => $reference,
                'gateway_checkout_url' => $redirectUrl,
            ]),
        ]);

        return [
            'success' => true,
            'gateway' => 'test_gateway',
            'payment_id' => $payment->id,
            'invoice_number' => $reference,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $redirectUrl,
            'transaction_id' => 'TEST-'.$reference,
            'payment_details' => ['payment_url' => $redirectUrl],
        ];
    }

    /**
     * Apply a sandbox simulation (`simulate` = success|failure|cancel).
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $payment = $this->resolvePayment($data);

        if (! $payment) {
            throw new \Exception('Payment not found for test_gateway callback.');
        }

        $simulate = strtolower((string) ($data['simulate'] ?? ''));

        if (! in_array($simulate, [self::SIMULATE_SUCCESS, self::SIMULATE_FAILURE, self::SIMULATE_CANCEL], true)) {
            return $this->verifyPayment($payment, $gateway);
        }

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'test_simulation' => $simulate,
            ]),
        ]);

        return $this->applySimulation($payment, $simulate);
    }

    /**
     * Re-apply the recorded simulation; a paid payment is already final.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        if ($payment->payment_status === Payment::STATUS_COMPLETED) {
            return $payment;
        }

        $simulation = (string) ($payment->payment_details['test_simulation'] ?? '');

        if (! in_array($simulation, [self::SIMULATE_SUCCESS, self::SIMULATE_FAILURE, self::SIMULATE_CANCEL], true)) {
            return $payment;
        }

        return $this->applySimulation($payment, $simulation);
    }

    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        return [
            'success' => false,
            'message' => "Refunds are not supported for gateway: {$gateway->code}",
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolvePayment(array $data): ?Payment
    {
        if (! empty($data['payment_id'])) {
            $payment = Payment::find($data['payment_id']);
            if ($payment) {
                return $payment;
            }
        }

        $reference = $data['reference'] ?? $data['invoice_number'] ?? null;

        if ($reference) {
            return Payment::where('invoice_number', $reference)->latest()->first();
        }

        return null;
    }

    protected function applySimulation(Payment $payment, string $simulate): Payment
    {
        if ($simulate === self::SIMULATE_SUCCESS) {
            return $this->completeTestPayment($payment);
        }

        if ($simulate === self::SIMULATE_FAILURE) {
            return $this->applyOutcome($payment, Payment::STATUS_FAILED, 'FAILED', 'Payment failed');
        }

        return $this->applyOutcome($payment, Payment::STATUS_CANCELLED, 'CANCELLED', 'Payment cancelled');
    }

    protected function completeTestPayment(Payment $payment): Payment
    {
        if ($payment->payment_status === Payment::STATUS_COMPLETED) {
            return $payment;
        }

        $transactionId = 'TEST-'.(string) $payment->invoice_number;

        $payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'paid_amount' => $payment->total_amount,
            'due_amount' => 0,
            'payment_date' => now(),
            'transaction_id' => $transactionId,
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'transaction_id' => $transactionId,
                'gateway_status' => 'COMPLETED',
                'verified_at' => now(),
            ]),
        ]);

        $this->applyPaymentSideEffects($payment, [
            'gateway' => 'test_gateway',
            'transaction_id' => $transactionId,
            'raw' => ['status' => 'COMPLETED', 'transaction_id' => $transactionId],
        ]);

        event(new \App\Events\PaymentProcessed($payment));

        return $payment;
    }

    protected function applyOutcome(Payment $payment, string $status, string $gatewayStatus, string $reason): Payment
    {
        if ($payment->payment_status === Payment::STATUS_COMPLETED || $payment->payment_status === $status) {
            return $payment;
        }

        $payment->update([
            'payment_status' => $status,
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'gateway_status' => $gatewayStatus,
                'failure_reason' => $reason,
                'verified_at' => now(),
            ]),
        ]);

        return $payment;
    }
}
