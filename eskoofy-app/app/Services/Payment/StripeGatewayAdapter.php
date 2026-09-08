<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StripeGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;
    use VerifiesWebhookSignature;

    /**
     * Initialize a hosted Stripe Checkout session.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $base = $gateway->sandbox_url ?: 'https://api.stripe.com/v1';

        $callbackUrl = $options['callback_url'] ?? $gateway->callback_url;

        $response = Http::withBasicAuth($gateway->api_key, '')
            ->asForm()
            ->post("{$base}/checkout/sessions", [
                'mode' => 'payment',
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => $gateway->currency ?: 'usd',
                'line_items[0][price_data][unit_amount]' => (int) round($payment->total_amount * 100),
                'line_items[0][price_data][product_data][name]' => Str::limit($payment->description ?: 'Eskoofy payment', 200),
                'success_url' => ($callbackUrl ?: url('/payment/status')).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $callbackUrl ?: url('/payment/status'),
                'metadata[payment_id]' => $payment->id,
                'metadata[invoice_number]' => $payment->invoice_number,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to create Stripe Checkout session: '.$response->body());
        }

        $session = $response->json();
        $sessionId = $session['id'] ?? null;

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'stripe_checkout_session' => $sessionId,
                'stripe_payment_intent' => $session['payment_intent'] ?? $payment->payment_details['stripe_payment_intent'] ?? null,
            ]),
        ]);

        return [
            'success' => true,
            'gateway' => 'stripe',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $session['url'] ?? null,
            'payment_details' => [
                'checkout_session' => $sessionId,
                'checkout_url' => $session['url'] ?? null,
            ],
        ];
    }

    /**
     * Process a Stripe webhook event.
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $object = $data['data']['object'] ?? [];
        $type = $data['type'] ?? '';

        $paymentId = $object['metadata']['payment_id'] ?? null;

        $payment = $paymentId ? Payment::find($paymentId) : null;

        if (! $payment) {
            $sessionId = $object['id'] ?? null;
            $intentId = $object['payment_intent'] ?? $object['id'] ?? null;

            $payment = $sessionId
                ? Payment::where('payment_details->stripe_checkout_session', $sessionId)->latest()->first()
                : Payment::where('payment_details->stripe_payment_intent', $intentId)->latest()->first();
        }

        if (! $payment) {
            throw new \Exception('Stripe callback missing payment reference');
        }

        $completedTypes = [
            'checkout.session.completed',
            'payment_intent.succeeded',
            'checkout.session.async_payment_succeeded',
        ];

        $paid = in_array($type, $completedTypes, true)
            || ($object['payment_status'] ?? null) === 'paid'
            || ($object['status'] ?? null) === 'succeeded';

        if ($paid) {
            if (! empty($object['payment_intent'])) {
                $payment->update([
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'stripe_payment_intent' => $object['payment_intent'],
                    ]),
                ]);
            }

            return $this->complete($payment, $gateway, [
                'transaction_id' => $object['payment_intent'] ?? $object['id'],
                'gateway_response' => $data,
            ]);
        }

        $payment->update([
            'payment_status' => Payment::STATUS_FAILED,
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'failure_reason' => $object['cancellation_reason'] ?? $object['status'] ?? 'payment failed',
            ]),
        ]);

        return $payment;
    }

    /**
     * Verify a Stripe Checkout session / PaymentIntent status.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        $sessionId = $payment->payment_details['stripe_checkout_session'] ?? null;
        $intentId = $payment->payment_details['stripe_payment_intent'] ?? null;

        $base = $gateway->sandbox_url ?: 'https://api.stripe.com/v1';

        if ($sessionId) {
            $response = Http::withBasicAuth($gateway->api_key, '')
                ->get("{$base}/checkout/sessions/{$sessionId}");

            if (! $response->successful() || $response->json('payment_status') !== 'paid') {
                return $payment;
            }

            $session = $response->json();

            if (! empty($session['payment_intent']) && empty($intentId)) {
                $payment->update([
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'stripe_payment_intent' => $session['payment_intent'],
                    ]),
                ]);
            }

            return $this->complete($payment, $gateway, [
                'transaction_id' => $session['payment_intent'] ?? $session['id'],
                'gateway_response' => $session,
            ]);
        }

        if (! $intentId) {
            return $payment;
        }

        $response = Http::withBasicAuth($gateway->api_key, '')
            ->get("{$base}/payment_intents/{$intentId}");

        if (! $response->successful() || $response->json('status') !== 'succeeded') {
            return $payment;
        }

        return $this->complete($payment, $gateway, [
            'transaction_id' => $intentId,
            'gateway_response' => $response->json(),
        ]);
    }

    /**
     * Verify a Stripe webhook signature (fail-closed).
     *
     * Stripe signs `$timestamp.$payload` with the webhook secret; expected
     * signature is transmitted under the `Stripe-Signature` header.
     */
    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool
    {
        $secret = $gateway->api_secret;

        if (empty($secret)) {
            return false;
        }

        $header = (string) $request->header('Stripe-Signature', '');
        if ($header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $expected = $parts['v1'] ?? '';

        if ($timestamp === '' || $expected === '') {
            return false;
        }

        $signedPayload = $timestamp.'.'.$request->getContent();
        $computed = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $computed);
    }

    /**
     * Refund a Stripe charge via the PaymentIntent.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        $base = $gateway->test_mode ? $gateway->sandbox_url ?: 'https://api.stripe.com/v1' : 'https://api.stripe.com/v1';

        $response = Http::withBasicAuth($gateway->api_key, '')
            ->asForm()
            ->post("{$base}/refunds", [
                'payment_intent' => $transactionId,
                'amount' => (int) round($amount * 100),
                'reason' => $reason,
            ]);

        if ($response->successful()) {
            $refund = $response->json();

            return [
                'success' => true,
                'transaction_id' => $refund['id'] ?? ('re_'.Str::random(16)),
                'gateway_response' => $refund,
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('error.message') ?? 'Stripe refund failed',
        ];
    }

    /**
     * Mark a Stripe payment as completed and apply side effects.
     *
     * @param  array<string, mixed>  $context
     */
    protected function complete(Payment $payment, PaymentGateway $gateway, array $context = []): Payment
    {
        $payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'paid_amount' => $payment->total_amount,
            'due_amount' => 0,
            'payment_date' => now(),
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'transaction_id' => $context['transaction_id'] ?? $payment->payment_details['transaction_id'] ?? null,
                'gateway_response' => $context['gateway_response'] ?? null,
                'verified_at' => now(),
            ]),
        ]);

        $this->applyPaymentSideEffects($payment, [
            'transaction_id' => $context['transaction_id'] ?? null,
            'gateway' => 'stripe',
        ]);

        event(new \App\Events\PaymentProcessed($payment));

        return $payment;
    }
}
