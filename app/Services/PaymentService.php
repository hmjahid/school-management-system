<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterFactory;

class PaymentService
{
    /**
     * Initialize a payment with the selected gateway.
     */
    public function initializePayment(Payment $payment, string $gatewayCode, array $options = []): array
    {
        $gateway = PaymentGateway::where('code', $gatewayCode)->firstOrFail();

        if (! $gateway->is_active) {
            throw new \Exception('Payment gateway is not active');
        }

        if ($gateway->is_online && ! $gateway->is_configured) {
            throw new \Exception('Payment gateway is not properly configured');
        }

        if (! $gateway->is_online) {
            return [
                'success' => true, 'gateway' => $gateway->code, 'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice_number, 'amount' => $payment->total_amount,
                'currency' => $payment->currency ?? $gateway->currency, 'redirect_url' => null,
                'offline_instructions' => $gateway->instructions,
                'payment_details' => [
                    'account_name' => config('payment.offline.account_name'),
                    'account_number' => config('payment.offline.account_number') ?? 'Not configured',
                    'bank_name' => config('payment.offline.bank_name') ?? 'Not configured',
                    'branch' => config('payment.offline.branch') ?? 'Not configured',
                    'routing_number' => config('payment.offline.routing_number') ?? 'Not configured',
                    'reference' => $payment->invoice_number,
                ],
            ];
        }

        return GatewayAdapterFactory::make($gatewayCode)->initialize($payment, $gateway, $options);
    }

    /**
     * Process a payment callback from the gateway.
     */
    public function processCallback(string $gatewayCode, array $data): Payment
    {
        $gateway = PaymentGateway::where('code', $gatewayCode)->firstOrFail();

        try {
            return GatewayAdapterFactory::make($gatewayCode)->processCallback($data, $gateway);
        } catch (\Exception $e) {
            throw new \Exception("Callback processing not implemented for gateway: {$gatewayCode}");
        }
    }

    /**
     * Verify a payment status with the gateway.
     */
    public function verifyPayment(Payment $payment): Payment
    {
        $gateway = $payment->payment_method === 'cash'
            ? null
            : PaymentGateway::where('code', $payment->payment_method)->first();

        if (! $gateway) {
            return $payment;
        }

        try {
            return GatewayAdapterFactory::make($gateway->code)->verifyPayment($payment, $gateway);
        } catch (\Exception $e) {
            return $payment;
        }
    }

    /**
     * Process a refund with the payment gateway.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array{success: bool, transaction_id: string|null, gateway_response: mixed, message?: string, code?: string}
     */
    public function processRefund(string $gatewayCode, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        if ($gatewayCode === 'test_gateway') {
            return [
                'success' => true,
                'transaction_id' => 'R-'.strtoupper(substr(md5($transactionId.$amount), 0, 12)),
                'gateway_response' => ['status' => 'Completed'],
            ];
        }

        $gateway = PaymentGateway::where('code', $gatewayCode)->first();

        if (! $gateway) {
            $fromConfig = config("payment.gateways.{$gatewayCode}");

            if (! is_array($fromConfig)) {
                return ['success' => false, 'message' => "Payment gateway not configured: {$gatewayCode}"];
            }

            $gateway = new PaymentGateway([
                'code' => $gatewayCode, 'name' => $gatewayCode, 'type' => 'other',
                'is_active' => true, 'is_online' => true, 'has_api' => true,
                'test_mode' => $fromConfig['test_mode'] ?? false,
                'sandbox_url' => $fromConfig['sandbox_url'] ?? null,
                'live_url' => $fromConfig['live_url'] ?? null,
                'api_key' => $fromConfig['api_key'] ?? null,
                'api_secret' => $fromConfig['api_secret'] ?? null,
                'api_username' => $fromConfig['api_username'] ?? null,
                'api_password' => $fromConfig['api_password'] ?? null,
                'currency' => $fromConfig['currency'] ?? 'BDT',
            ]);
        }

        try {
            return GatewayAdapterFactory::make($gatewayCode)->refund($gateway, $transactionId, $amount, $reason, $paymentDetails);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => "Refunds are not supported for gateway: {$gatewayCode}"];
        }
    }

    /**
     * Determine whether a given payment method supports refunds.
     */
    public function supportsRefunds(string $method): bool
    {
        return in_array($method, ['bkash', 'nagad', 'rocket', 'test_gateway'], true);
    }

    /**
     * Resolve gateway configuration from DB, falling back to config/payment.php.
     */
    public function resolveGatewayConfig(string $gatewayCode): ?array
    {
        $fromConfig = config("payment.gateways.{$gatewayCode}");

        if (! is_array($fromConfig)) {
            return null;
        }

        $gateway = PaymentGateway::where('code', $gatewayCode)->first();

        if ($gateway) {
            $fromConfig = array_merge($fromConfig, $gateway->getApiConfig());
            $fromConfig['base_url'] = $gateway->test_mode ? $gateway->sandbox_url : $gateway->live_url;
        } else {
            $fromConfig['base_url'] = $fromConfig['live_url'] ?? $fromConfig['sandbox_url'];
        }

        return $fromConfig;
    }
}
