<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BkashGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;
    use VerifiesWebhookSignature;

    /**
     * Initialize bKash payment.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        // Step 1: Get auth token
        $tokenResponse = Http::withHeaders([
            'username' => $config['api_username'],
            'password' => $config['api_password'],
        ])->post("$baseUrl/checkout/token/grant", [
            'app_key' => $config['api_key'],
            'app_secret' => $config['api_secret'],
        ]);

        if (! $tokenResponse->successful()) {
            Log::error('Failed to get bKash token', $tokenResponse->json());
            throw new \Exception('Failed to initialize bKash payment');
        }

        $tokenData = $tokenResponse->json();
        $idToken = $tokenData['id_token'];

        // Step 2: Create payment
        $paymentResponse = Http::withHeaders([
            'Authorization' => $idToken,
            'X-APP-Key' => $config['api_key'],
        ])->post("$baseUrl/checkout/create", [
            'mode' => '0000', // For test mode
            'payerReference' => 'INV'.$payment->invoice_number,
            'callbackURL' => $gateway->callback_url,
            'amount' => number_format($payment->total_amount, 2, '.', ''),
            'currency' => $payment->currency ?? $gateway->currency,
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->invoice_number,
        ]);

        if (! $paymentResponse->successful()) {
            Log::error('Failed to create bKash payment', $paymentResponse->json());
            throw new \Exception('Failed to create bKash payment');
        }

        $paymentData = $paymentResponse->json();

        // Store the payment ID and token for verification
        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'bkash_payment_id' => $paymentData['paymentID'],
                'bkash_token' => $idToken,
            ]),
        ]);

        return [
            'success' => true,
            'gateway' => 'bkash',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $paymentData['bkashURL'],
            'payment_details' => [
                'payment_id' => $paymentData['paymentID'],
                'create_time' => $paymentData['createTime'],
                'org_logo' => $paymentData['orgLogo'],
            ],
        ];
    }

    /**
     * Process bKash callback.
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $payment = Payment::where('invoice_number', $data['merchantInvoiceNumber'])
            ->orWhere('payment_details->bkash_payment_id', $data['paymentID'])
            ->firstOrFail();

        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        // Verify the payment with bKash
        $verifyResponse = Http::withHeaders([
            'Authorization' => $payment->payment_details['bkash_token'] ?? '',
            'X-APP-Key' => $config['api_key'],
        ])->post("$baseUrl/checkout/execute", [
            'paymentID' => $data['paymentID'],
        ]);

        if (! $verifyResponse->successful()) {
            Log::error('Failed to verify bKash payment', $verifyResponse->json());
            throw new \Exception('Failed to verify bKash payment');
        }

        $verifyData = $verifyResponse->json();

        // Update payment status based on bKash response
        if (isset($verifyData['transactionStatus']) && $verifyData['transactionStatus'] === 'Completed') {
            $payment->update([
                'payment_status' => Payment::STATUS_COMPLETED,
                'paid_amount' => $payment->total_amount,
                'due_amount' => 0,
                'payment_date' => now(),
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'transaction_id' => $verifyData['trxID'],
                    'payment_status' => $verifyData['transactionStatus'],
                    'payment_method_details' => $verifyData,
                ]),
            ]);

            $this->applyPaymentSideEffects($payment, [
                'gateway' => $gateway->code,
                'transaction_id' => $verifyData['trxID'] ?? null,
                'raw' => $verifyData,
            ]);

            // Trigger payment success event
            // event(new \App\Events\PaymentProcessed($payment));
        } else {
            $payment->update([
                'payment_status' => Payment::STATUS_FAILED,
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'transaction_id' => $verifyData['trxID'] ?? null,
                    'payment_status' => $verifyData['transactionStatus'] ?? 'Failed',
                    'failure_reason' => $verifyData['statusMessage'] ?? 'Payment verification failed',
                    'payment_method_details' => $verifyData,
                ]),
            ]);
        }

        return $payment;
    }

    /**
     * Verify bKash payment status.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        if (empty($payment->payment_details['bkash_payment_id'])) {
            return $payment;
        }

        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        // Get a new token for verification
        $tokenResponse = Http::withHeaders([
            'username' => $config['api_username'],
            'password' => $config['api_password'],
        ])->post("$baseUrl/checkout/token/grant", [
            'app_key' => $config['api_key'],
            'app_secret' => $config['api_secret'],
        ]);

        if (! $tokenResponse->successful()) {
            Log::error('Failed to get bKash token for verification', $tokenResponse->json());

            return $payment;
        }

        $tokenData = $tokenResponse->json();
        $idToken = $tokenData['id_token'];

        // Query payment status
        $response = Http::withHeaders([
            'Authorization' => $idToken,
            'X-APP-Key' => $config['api_key'],
        ])->post("$baseUrl/checkout/payment/status", [
            'paymentID' => $payment->payment_details['bkash_payment_id'],
        ]);

        if (! $response->successful()) {
            Log::error('Failed to verify bKash payment status', $response->json());

            return $payment;
        }

        $statusData = $response->json();

        // Update payment status if it has changed
        if (isset($statusData['transactionStatus'])) {
            $newStatus = $this->mapBkashStatus($statusData['transactionStatus']);

            if ($newStatus !== $payment->payment_status) {
                $payment->update([
                    'payment_status' => $newStatus,
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'last_verified_at' => now(),
                        'verification_response' => $statusData,
                    ]),
                ]);

                if ($newStatus === Payment::STATUS_COMPLETED) {
                    $payment->update([
                        'paid_amount' => $payment->total_amount,
                        'due_amount' => 0,
                        'payment_date' => $statusData['completedTime'] ?? now(),
                    ]);

                    // Trigger payment success event if it was just completed
                    if ($payment->wasChanged('payment_status')) {
                        event(new \App\Events\PaymentProcessed($payment));
                    }
                }
            }
        }

        return $payment;
    }

    /**
     * Process a bKash refund.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        $config = $gateway->getApiConfig();
        $base = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        $tokenResponse = Http::withHeaders([
            'username' => $config['api_username'] ?? null,
            'password' => $config['api_password'] ?? null,
        ])->post("$base/tokenized/checkout/token/grant", [
            'app_key' => $config['api_key'] ?? null,
            'app_secret' => $config['api_secret'] ?? null,
        ]);

        if (! $tokenResponse->successful()) {
            return ['success' => false, 'message' => 'Failed to authenticate with bKash'];
        }

        $idToken = $tokenResponse->json()['id_token'] ?? null;

        if (! $idToken) {
            return ['success' => false, 'message' => 'Failed to authenticate with bKash'];
        }

        $response = Http::withHeaders([
            'Authorization' => $idToken,
            'X-APP-Key' => $config['api_key'] ?? null,
        ])->post("{$base}/tokenized/checkout/payment/refund", [
            'paymentID' => $transactionId,
            'amount' => number_format($amount, 2, '.', ''),
            'reason' => $reason,
            'currency' => 'BDT',
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['statusCode'] ?? null) === '0000') {
            return [
                'success' => true,
                'transaction_id' => $data['refundTrxID'] ?? ('R-'.strtoupper(substr(md5($transactionId.$amount), 0, 12))),
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => $data['statusMessage'] ?? $data['errorMessage'] ?? 'bKash refund failed',
        ];
    }

    /**
     * Map bKash status to our payment status.
     */
    protected function mapBkashStatus(string $bkashStatus): string
    {
        $statusMap = [
            'Initiated' => Payment::STATUS_PENDING,
            'Incomplete' => Payment::STATUS_PROCESSING,
            'Completed' => Payment::STATUS_COMPLETED,
            'Failed' => Payment::STATUS_FAILED,
            'Canceled' => Payment::STATUS_CANCELLED,
            'Expired' => Payment::STATUS_EXPIRED,
            'Refunded' => Payment::STATUS_REFUNDED,
        ];

        return $statusMap[$bkashStatus] ?? Payment::STATUS_PENDING;
    }

    /**
     * Verify a bKash webhook/IPN signature (fail-closed).
     *
     * @see \App\Services\Payment\VerifiesWebhookSignature
     */
    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool
    {
        return $this->verifyHmacSignature($request, $gateway, 'X-Bkash-Signature');
    }
}
