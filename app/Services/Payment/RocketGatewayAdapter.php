<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RocketGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;

    /**
     * Initialize Rocket payment.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $config = $gateway->getApiConfig();

        // Generate a unique transaction ID
        $transactionId = 'TXN'.time().Str::random(6);

        // Store the transaction ID for verification
        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'rocket_transaction_id' => $transactionId,
            ]),
        ]);

        // For Rocket, we'll return the payment details for the frontend to handle
        return [
            'success' => true,
            'gateway' => 'rocket',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $gateway->success_url.'?payment_id='.$payment->id,
            'payment_details' => [
                'transaction_id' => $transactionId,
                'biller_id' => $config['biller_id'] ?? 'SCHOOL',
                'bill_number' => $payment->invoice_number,
                'amount' => $payment->total_amount,
                'instructions' => 'Dial *322# and follow the instructions to complete the payment.',
            ],
        ];
    }

    /**
     * Process Rocket callback.
     *
     * Rocket has no real callback.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        throw new \Exception("Callback processing not implemented for gateway: {$gateway->code}");
    }

    /**
     * Process Rocket payment verification.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        // In a real implementation, you would verify the payment with Rocket's API
        // For this example, we'll assume the verification was successful

        $verificationData = $gateway->extra_attributes ?? [];

        $payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'paid_amount' => $payment->total_amount,
            'due_amount' => 0,
            'payment_date' => now(),
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'transaction_id' => $verificationData['transaction_id'] ?? ('TXN'.time().Str::random(6)),
                'payment_status' => 'Completed',
                'payment_method_details' => $verificationData,
                'verified_at' => now(),
            ]),
        ]);

        // Trigger payment success event
        event(new \App\Events\PaymentProcessed($payment));

        return $payment;
    }

    /**
     * Process a Rocket refund.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        $config = $gateway->getApiConfig();
        $base = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        $tokenResponse = Http::post("$base/token", [
            'username' => $config['api_username'] ?? null,
            'password' => $config['api_password'] ?? null,
        ]);

        if (! $tokenResponse->successful()) {
            return ['success' => false, 'message' => 'Failed to authenticate with Rocket'];
        }

        $accessToken = $tokenResponse->json()['access_token'] ?? null;

        $response = Http::withToken($accessToken)->post("$base/refund", [
            'transaction_id' => $transactionId,
            'amount' => number_format($amount, 2, '.', ''),
            'reason' => $reason,
        ]);

        $data = $response->json();

        if ($response->successful() && in_array($data['status'] ?? null, ['success', 'Completed'], true)) {
            return [
                'success' => true,
                'transaction_id' => $data['refund_id'] ?? ('R-'.strtoupper(substr(md5($transactionId.$amount), 0, 12))),
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => $data['message'] ?? 'Rocket refund failed',
        ];
    }
}
