<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Nagad payment gateway (Bangladesh). DFS API.
 */
class NagadGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'nagad';
    }

    public function name(): string
    {
        return 'Nagad';
    }

    public function isConfigured(): bool
    {
        return (string) ($this->setting('merchant_id') ?? '') !== ''
            && (string) ($this->setting('api_key') ?? '') !== '';
    }

    private function baseUrl(): string
    {
        return ($this->setting('test_mode') ?? true) === true
            ? (string) ($this->setting('sandbox_url') ?? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs')
            : (string) ($this->setting('live_url') ?? 'https://api.mynagad.com/api/dfs');
    }

    public function process(array $order, array $payment): array
    {
        $amount    = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $reference = (string) ($payment['reference'] ?? '');
        $merchant  = (string) ($this->setting('merchant_id') ?? '');

        if ($amount <= 0) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Invalid amount', 'raw' => null];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Nagad is not configured.', 'raw' => null];
        }

        $response = $this->http(
            $this->baseUrl() . '/check-out/initialize/' . $merchant . '/' . $reference,
            [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'X-KM-Api-Key' => (string) ($this->setting('api_key') ?? ''),
            ],
            json_encode([
                'merchantId'   => $merchant,
                'orderId'      => $reference,
                'amount'       => (string) (int) round($amount * 100),
                'currencyCode' => '050',
                'callbackUrl'  => $order['return_url'] ?? '',
            ])
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];
        $redirect = (string) ($decoded['callBackUrl'] ?? ($order['return_url'] ?? ''));

        if ($response['ok'] && $redirect !== '') {
            return [
                'success'        => true,
                'transaction_id' => $reference,
                'message'        => 'Redirecting to Nagad payment.',
                'redirect_url'   => $redirect,
                'raw'            => $decoded,
            ];
        }

        return [
            'success'        => false,
            'transaction_id' => null,
            'message'        => $decoded['message'] ?? 'Nagad payment initialization failed.',
            'raw'            => $decoded,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        $ref = (string) ($data['reference'] ?? $data['order_id'] ?? $payment['reference'] ?? '');
        if ($ref === '') {
            return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Missing reference.', 'raw' => null];
        }

        $status = strtolower((string) ($data['status'] ?? $data['trx_status'] ?? ''));
        $ok = $status === 'success' || $status === 'completed';

        if ($ok) {
            $trxId = (string) ($data['trx_id'] ?? $data['transaction_id'] ?? $ref);
            $this->markPaid($payment, $trxId, ['raw' => json_encode($data)]);
            $this->logProcessed($payment, $trxId);
        }

        return [
            'success'        => $ok,
            'transaction_id' => (string) ($data['trx_id'] ?? $ref),
            'status'         => $status ?: 'pending',
            'message'        => $ok ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => $data,
        ];
    }
}