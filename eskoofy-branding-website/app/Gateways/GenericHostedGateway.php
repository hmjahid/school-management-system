<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\DatabaseInterface;

/**
 * Config-driven hosted gateway.
 *
 * Serves any BD aggregator/hosted provider whose credentials and endpoints are
 * stored in `config/gateways.php` (overridable per install from the settings
 * table) without a bespoke class. The checkout URL is built from the
 * `checkout_url_template` once credentials are present, and the callback is
 * verified through `verify_url`.
 */
class GenericHostedGateway extends AbstractGateway
{
    private string $code;

    public function __construct(?DatabaseInterface $db = null, string $code = '')
    {
        parent::__construct($db);
        $this->code = $code;
    }

    public function id(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return (string) ($this->setting('name') ?? ucfirst($this->code));
    }

    public function isConfigured(): bool
    {
        foreach (['api_key', 'merchant_id', 'app_key', 'secret_key'] as $key) {
            if ((string) ($this->setting($key) ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    private function endpoint(string $testKey, string $liveKey): string
    {
        $test = ($this->setting('test_mode') ?? true) === true;
        $url  = (string) ($this->setting($test ? $testKey : $liveKey) ?? '');

        return $url !== '' ? $url : (string) ($this->setting($testKey) ?? '');
    }

    /**
     * @return array<string, string>
     */
    private function tokens(array $order, array $payment): array
    {
        return [
            '{amount}'    => number_format((float) ($order['amount'] ?? $payment['amount'] ?? 0), 2, '.', ''),
            '{currency}'  => (string) ($order['currency'] ?? $payment['currency'] ?? ''),
            '{reference}' => (string) ($payment['reference'] ?? ''),
            '{invoice}'   => (string) ($payment['reference'] ?? ''),
            '{callback}'  => rawurlencode((string) ($order['return_url'] ?? '')),
            '{cancel}'    => rawurlencode((string) ($order['cancel_url'] ?? '')),
            '{api_key}'   => rawurlencode((string) ($this->setting('api_key') ?? '')),
        ];
    }

    public function process(array $order, array $payment): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'status' => 'failed', 'message' => $this->name() . ' is not configured.', 'raw' => null];
        }

        $amount   = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $template = (string) ($this->setting('checkout_url_template') ?? '');

        if ($amount <= 0 || $template === '') {
            return ['success' => false, 'transaction_id' => null, 'status' => 'failed', 'message' => $this->name() . ' has no checkout URL configured.', 'raw' => null];
        }

        $redirect = strtr($template, $this->tokens($order, $payment));

        return [
            'success'        => true,
            'transaction_id' => (string) ($payment['reference'] ?? ''),
            'status'         => 'pending',
            'message'        => 'Redirecting to ' . $this->name() . '.',
            'redirect_url'   => $redirect,
            'raw'            => null,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        if (($payment['status'] ?? '') === 'paid') {
            return ['success' => true, 'transaction_id' => $payment['transaction_id'] ?? null, 'status' => 'paid', 'message' => 'Payment already verified.', 'raw' => null];
        }

        $invoice   = (string) ($data['invoice_id'] ?? ($payment['transaction_id'] ?? ($payment['reference'] ?? '')));
        $verifyUrl = (string) ($this->setting('verify_url') ?? '');

        if ($invoice === '' || $verifyUrl === '' || !$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Missing invoice or ' . $this->name() . ' not configured.', 'raw' => null];
        }

        $url = str_replace(['{reference}', '{invoice}'], [$invoice, $invoice], $verifyUrl);

        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        $header  = (string) ($this->setting('signature_header') ?? 'X-Webhook-Signature');
        $apiKey  = (string) ($this->setting('api_key') ?? '');
        if ($apiKey !== '') {
            $headers[$header] = $apiKey;
        }

        $response = $this->http($url, $headers, (string) json_encode(['invoice_id' => $invoice]));
        $decoded  = is_array($response['decoded']) ? $response['decoded'] : [];

        $path     = (string) ($this->setting('verify_success_path') ?? 'status');
        $expected = (string) ($this->setting('verify_success_value') ?? 'COMPLETED');
        $status   = strtoupper((string) ($decoded[$path] ?? ''));
        $ok       = $response['ok'] && $status === strtoupper($expected);

        if ($ok) {
            $trxId = (string) ($decoded['transaction_id'] ?? $invoice);
            $this->markPaid($payment, $trxId, ['raw' => (string) json_encode($decoded)]);
            $this->logProcessed($payment, $trxId);
        }

        return [
            'success'        => $ok,
            'transaction_id' => (string) ($decoded['transaction_id'] ?? $invoice),
            'status'         => $status !== '' ? strtolower($status) : 'pending',
            'message'        => $ok ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => $decoded,
        ];
    }
}
