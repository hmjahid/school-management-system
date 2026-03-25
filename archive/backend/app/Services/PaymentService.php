<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Initialize a payment with the selected gateway.
     */
    public function initializePayment(Payment $payment, string $gatewayCode, array $options = []): array
    {
        $gateway = PaymentGateway::where('code', $gatewayCode)->firstOrFail();
        
        // Validate gateway is active and configured
        if (!$gateway->is_active) {
            throw new \Exception("Payment gateway is not active");
        }
        
        if ($gateway->is_online && !$gateway->is_configured) {
            throw new \Exception("Payment gateway is not properly configured");
        }
        
        // For offline payments, just return the payment details
        if (!$gateway->is_online) {
            return [
                'success' => true,
                'gateway' => $gateway->code,
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'amount' => $payment->total_amount,
                'currency' => $payment->currency ?? $gateway->currency,
                'redirect_url' => null,
                'offline_instructions' => $gateway->instructions,
                'payment_details' => [
                    'account_name' => config('app.name') . ' School',
                    'account_number' => '1234567890',
                    'bank_name' => 'Example Bank',
                    'branch' => 'Main Branch',
                    'routing_number' => '123456789',
                    'reference' => $payment->invoice_number,
                ],
            ];
        }
        
        // For online payments, initialize with the specific gateway
        $method = 'initialize' . Str::studly($gateway->code) . 'Payment';
        
        if (method_exists($this, $method)) {
            return $this->$method($payment, $gateway, $options);
        }
        
        throw new \Exception("Payment method not implemented: {$gateway->code}");
    }
    
    /**
     * Process a payment callback from the gateway.
     */
    public function processCallback(string $gatewayCode, array $data): Payment
    {
        $gateway = PaymentGateway::where('code', $gatewayCode)->firstOrFail();
        
        $method = 'process' . Str::studly($gateway->code) . 'Callback';
        
        if (method_exists($this, $method)) {
            return $this->$method($data, $gateway);
        }
        
        throw new \Exception("Callback processing not implemented for gateway: {$gateway->code}");
    }
    
    /**
     * Verify a payment status with the gateway.
     */
    public function verifyPayment(Payment $payment): Payment
    {
        $gateway = $payment->payment_method === 'cash' 
            ? null 
            : PaymentGateway::where('code', $payment->payment_method)->first();
        
        if (!$gateway) {
            return $payment; // No verification for cash payments or unknown gateways
        }
        
        $method = 'verify' . Str::studly($gateway->code) . 'Payment';
        
        if (method_exists($this, $method)) {
            return $this->$method($payment, $gateway);
        }
        
        return $payment; // Return as-is if verification not implemented
    }
    
    /**
     * Initialize bKash payment.
     */
    protected function initializeBkashPayment(Payment $payment, PaymentGateway $gateway, array $options = []): array
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
        
        if (!$tokenResponse->successful()) {
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
            'payerReference' => 'INV' . $payment->invoice_number,
            'callbackURL' => $gateway->callback_url,
            'amount' => number_format($payment->total_amount, 2, '.', ''),
            'currency' => $payment->currency ?? $gateway->currency,
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->invoice_number,
        ]);
        
        if (!$paymentResponse->successful()) {
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
     */
    protected function processBkashCallback(array $data, PaymentGateway $gateway): Payment
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
        
        if (!$verifyResponse->successful()) {
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
            
            // Trigger payment success event
            event(new \App\Events\PaymentProcessed($payment));
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
    protected function verifyBkashPayment(Payment $payment, PaymentGateway $gateway): Payment
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
        
        if (!$tokenResponse->successful()) {
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
        
        if (!$response->successful()) {
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
     * Initialize Nagad payment.
     */
    protected function initializeNagadPayment(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;
        
        // Generate a random string for request ID
        $requestId = Str::uuid()->toString();
        
        // Step 1: Initialize payment
        $initResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-KM-IP-V4' => request()->ip(),
            'X-KM-Client-Type' => 'PC_WEB',
            'X-KM-Api-Version' => 'v-0.2.0',
        ])->post("$baseUrl/checkout/initialize/", [
            'accountNumber' => $config['merchant_account'],
            'dateTime' => now()->format('YmdHis'),
            'additionalMerchantInfo' => [
                'reference' => $payment->invoice_number,
                'purpose' => 'School Fee Payment',
            ],
            'amount' => (string) $payment->total_amount,
            'orderId' => $payment->invoice_number,
            'reference' => $payment->invoice_number,
        ]);
        
        if (!$initResponse->successful()) {
            Log::error('Failed to initialize Nagad payment', $initResponse->json());
            throw new \Exception('Failed to initialize Nagad payment');
        }
        
        $initData = $initResponse->json();
        
        // Store the payment ID for verification
        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'nagad_payment_id' => $initData['paymentReferenceId'],
                'nagad_order_id' => $initData['orderId'],
            ]),
        ]);
        
        // Step 2: Complete the payment (this would be called from the frontend after user completes payment)
        // For now, we'll return the payment URL
        $paymentUrl = $initData['callBackUrl'] . '?paymentRefId=' . $initData['paymentReferenceId'];
        
        return [
            'success' => true,
            'gateway' => 'nagad',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $paymentUrl,
            'payment_details' => [
                'payment_reference_id' => $initData['paymentReferenceId'],
                'order_id' => $initData['orderId'],
                'challenge' => $initData['challenge'],
            ],
        ];
    }
    
    /**
     * Process Nagad callback.
     */
    protected function processNagadCallback(array $data, PaymentGateway $gateway): Payment
    {
        $payment = Payment::where('invoice_number', $data['orderId'])
            ->orWhere('payment_details->nagad_payment_id', $data['paymentRefId'])
            ->firstOrFail();
            
        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;
        
        // Verify the payment with Nagad
        $verifyResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-KM-IP-V4' => request()->ip(),
            'X-KM-Client-Type' => 'PC_WEB',
            'X-KM-Api-Version' => 'v-0.2.0',
        ])->post("$baseUrl/verify/payment/", [
            'paymentRefId' => $data['paymentRefId'],
        ]);
        
        if (!$verifyResponse->successful()) {
            Log::error('Failed to verify Nagad payment', $verifyResponse->json());
            throw new \Exception('Failed to verify Nagad payment');
        }
        
        $verifyData = $verifyResponse->json();
        
        // Update payment status based on Nagad response
        if (isset($verifyData['status']) && $verifyData['status'] === 'Success') {
            $payment->update([
                'payment_status' => Payment::STATUS_COMPLETED,
                'paid_amount' => $payment->total_amount,
                'due_amount' => 0,
                'payment_date' => now(),
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'transaction_id' => $verifyData['paymentId'] ?? null,
                    'payment_status' => $verifyData['status'],
                    'payment_method_details' => $verifyData,
                ]),
            ]);
            
            // Trigger payment success event
            event(new \App\Events\PaymentProcessed($payment));
        } else {
            $payment->update([
                'payment_status' => Payment::STATUS_FAILED,
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'transaction_id' => $verifyData['paymentId'] ?? null,
                    'payment_status' => $verifyData['status'] ?? 'Failed',
                    'failure_reason' => $verifyData['statusMessage'] ?? 'Payment verification failed',
                    'payment_method_details' => $verifyData,
                ]),
            ]);
        }
        
        return $payment;
    }
    
    /**
     * Initialize Rocket payment.
     */
    protected function initializeRocketPayment(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;
        
        // Generate a unique transaction ID
        $transactionId = 'TXN' . time() . Str::random(6);
        
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
            'redirect_url' => $gateway->success_url . '?payment_id=' . $payment->id,
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
     * Process Rocket payment verification.
     */
    public function verifyRocketPayment(Payment $payment, array $verificationData): Payment
    {
        // In a real implementation, you would verify the payment with Rocket's API
        // For this example, we'll assume the verification was successful
        
        $payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'paid_amount' => $payment->total_amount,
            'due_amount' => 0,
            'payment_date' => now(),
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'transaction_id' => $verificationData['transaction_id'] ?? ('TXN' . time() . Str::random(6)),
                'payment_status' => 'Completed',
                'payment_method_details' => $verificationData,
                'verified_at' => now(),
            ]),
        ]);
        
        // Trigger payment success event
        event(new \App\Events\PaymentProcessed($payment));
        
        return $payment;
    }
}
