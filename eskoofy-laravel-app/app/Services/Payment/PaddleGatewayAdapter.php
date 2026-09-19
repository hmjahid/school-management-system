<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaddleGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;

    /**
     * Initialize a Paddle (Classic) checkout checkout page.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $checkoutUrl = $gateway->test_mode
            ? ($gateway->sandbox_url ?: 'https://sandbox-checkout.paddle.com/api/1.0/orders?product=')
            : ($gateway->live_url ?: 'https://checkout.paddle.com/api/1.0/orders?product=');

        $params = http_build_query([
            'product_id' => $gateway->api_username,
            'price' => number_format($payment->total_amount, 2, '.', ''),
            'currency' => $gateway->currency ?: 'USD',
            'custom_message' => $payment->invoice_number,
            'passthrough' => json_encode([
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
            ]),
        ]);

        $separator = str_contains($checkoutUrl, '?') ? '&' : '?';
        $url = rtrim($checkoutUrl, '?&').$separator.$params;

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'paddle_checkout_url' => $url,
            ]),
        ]);

        return [
            'success' => true,
            'gateway' => 'paddle',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $url,
            'payment_details' => [
                'checkout_url' => $url,
                'vendor_id' => $gateway->api_key,
            ],
        ];
    }

    /**
     * Process a Paddle webhook (alerts) payload.
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $alertName = (string) ($data['alert_name'] ?? '');

        // Recover the payment via the passthrough metadata.
        $passthrough = is_array($data['passthrough'] ?? null)
            ? $data['passthrough']
            : (json_decode((string) ($data['passthrough'] ?? ''), true) ?: []);

        $payment = Payment::find($passthrough['payment_id'] ?? null)
            ?? (Payment::where('invoice_number', $passthrough['invoice_number'] ?? null)->latest()->first());

        if (! $payment) {
            throw new \Exception('Payment not found for Paddle alert: '.$alertName);
        }

        if ($alertName === 'payment_succeeded') {
            return $this->complete($payment, $gateway, [
                'transaction_id' => $data['checkout_id'] ?? null,
                'gateway_response' => $data,
            ]);
        }

        if (in_array($alertName, ['payment_refunded', 'high_risk_refunded', 'refund_issued', 'payment_refunded_v3'], true)) {
            $payment->update([
                'payment_status' => Payment::STATUS_REFUNDED,
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'refund_reason' => $data['refund_reason'] ?? $data['amount_refunded'] ?? null,
                ]),
            ]);

            return $payment;
        }

        return $payment;
    }

    /**
     * Verify a Paddle transaction. Classic has no open status endpoint, so we
     * only confirm completion when a checkout id is already recorded.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        $checkoutId = $payment->payment_details['paddle_checkout_id'] ?? null;

        if (! $checkoutId) {
            return $payment;
        }

        // Paddle Classic does not expose a transaction lookup API; the
        // webhook (`payment_succeeded`) is the source of truth.
        return $payment;
    }

    /**
     * Verify a Paddle webhook signature (fail-closed).
     *
     * Paddle Classic signs alerts with an MD5 hash built from the PHP public
     * key and the alphabetically-sorted form fields, transmitted in the
     * `P_PHP_SIGNATURE` request body field.
     */
    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool
    {
        $publicKey = $gateway->extra_attributes['paddle_public_key'] ?? null;

        if (empty($publicKey)) {
            return false;
        }

        $fields = $request->except(['p_signature']);
        ksort($fields);

        $publicKey = str_replace("\n", '', $publicKey);
        $received = $request->input('p_signature');

        if (empty($received)) {
            return false;
        }

        $expected = '';
        foreach ($fields as $key => $value) {
            $expected .= "{$key}={$value}";
        }
        $expected .= $publicKey;

        return hash_equals(md5($expected), (string) $received);
    }

    /**
     * Refund a Paddle payment.
     *
     * Paddle Classic has no open refund API — refunds are issued (via the
     * Paddle dashboard) and reported back through the `refund_issued` alert.
     * Refund execution is therefore explicitly offline-dashboard-only.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        return [
            'success' => false,
            'offline' => true,
            'message' => 'Paddle refunds are issued from the Paddle dashboard; the refunded state is applied via the refund_issued webhook alert.',
        ];
    }

    /**
     * Mark a Paddle payment as completed and apply side effects.
     *
     * @param  array<string, mixed>  $context
     */
    protected function complete(Payment $payment, PaymentGateway $gateway, array $context = []): Payment
    {
        $checkoutId = $context['transaction_id'] ?? $payment->payment_details['paddle_checkout_id'] ?? 'PADDLE-'.Str::random(12);

        $payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'paid_amount' => $payment->total_amount,
            'due_amount' => 0,
            'payment_date' => now(),
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'paddle_checkout_id' => $checkoutId,
                'transaction_id' => $checkoutId,
                'gateway_response' => $context['gateway_response'] ?? null,
                'verified_at' => now(),
            ]),
        ]);

        $this->applyPaymentSideEffects($payment, [
            'transaction_id' => $checkoutId,
            'gateway' => 'paddle',
        ]);

        event(new \App\Events\PaymentProcessed($payment));

        return $payment;
    }
}
