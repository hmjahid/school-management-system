<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * bKash payment gateway (Bangladesh). Tokenized checkout API.
 */
class BkashGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'bkash';
    }

    public function name(): string
    {
        return 'bKash';
    }

    public function isConfigured(): bool
    {
        return (string) ($this->setting('app_key') ?? '') !== ''
            && (string) ($this->setting('app_secret') ?? '') !== '';
    }

    private function baseUrl(): string
    {
        return ($this->setting('test_mode') ?? true) === true
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : rtrim((string) ($this->setting('live_url') ?? 'https://tokenized.pay.bka.sh/v1.2.0-beta'), '/');
    }

    private function token(): ?string
    {
        $response = $this->http(
            $this->baseUrl() . '/tokenized/checkout/token/grant',
            [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'username'     => (string) ($this->setting('username') ?? ''),
                'password'     => (string) ($this->setting('password') ?? ''),
            ],
            json_encode([
                'app_key'    => (string) ($this->setting('app_key') ?? ''),
                'app_secret' => (string) ($this->setting('app_secret') ?? ''),
            ])
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];

        return $response['ok'] && isset($decoded['id_token']) ? (string) $decoded['id_token'] : null;
    }

    public function process(array $order, array $payment): array
    {
        $amount    = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $reference = (string) ($payment['reference'] ?? '');
        $token     = $this->token();

        if ($amount <= 0) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Invalid amount', 'raw' => null];
        }
        if ($token === null) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'bKash is not configured or token failed.', 'raw' => null];
        }

        $response = $this->http(
            $this->baseUrl() . '/tokenized/checkout/create',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => $token,
                'X-APP-Key'     => (string) ($this->setting('app_key') ?? ''),
            ],
            json_encode([
                'mode'                  => '0011',
                'payerReference'        => $reference,
                'callbackURL'           => $order['return_url'] ?? '',
                'amount'                => number_format($amount, 2, '.', ''),
                'currency'              => 'BDT',
                'intent'                => 'sale',
                'merchantInvoiceNumber' => $reference,
            ])
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];

        if ($response['ok'] && isset($decoded['bkashURL'])) {
            return [
                'success'        => true,
                'transaction_id' => (string) ($decoded['paymentID'] ?? ''),
                'message'        => 'Redirecting to bKash payment.',
                'redirect_url'   => (string) $decoded['bkashURL'],
                'raw'            => $decoded,
            ];
        }

        return [
            'success'        => false,
            'transaction_id' => null,
            'message'        => $decoded['errorMessage'] ?? 'bKash payment creation failed.',
            'raw'            => $decoded,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        $paymentId = (string) ($data['paymentID'] ?? $payment['transaction_id'] ?? '');
        $token     = $this->token();
        if ($paymentId === '' || $token === null) {
            return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Missing payment / bKash not configured.', 'raw' => null];
        }

        $response = $this->http(
            $this->baseUrl() . '/tokenized/checkout/execute',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => $token,
                'X-APP-Key'     => (string) ($this->setting('app_key') ?? ''),
            ],
            json_encode(['paymentID' => $paymentId])
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];
        $trxStatus = (string) ($decoded['transactionStatus'] ?? '');
        $ok = $trxStatus === 'Completed';

        if ($ok) {
            $trxId = (string) ($decoded['trxID'] ?? $paymentId);
            $this->markPaid($payment, $trxId, ['raw' => json_encode($decoded)]);
            $this->logProcessed($payment, $trxId);
        }

        return [
            'success'        => $ok,
            'transaction_id' => (string) ($decoded['trxID'] ?? $paymentId),
            'status'         => strtolower($trxStatus),
            'message'        => $ok ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => $decoded,
        ];
    }
}