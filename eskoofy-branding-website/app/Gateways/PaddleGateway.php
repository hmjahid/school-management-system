<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Paddle payment gateway (international). Classic checkout / subscription.
 */
class PaddleGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'paddle';
    }

    public function name(): string
    {
        return 'Paddle';
    }

    public function isConfigured(): bool
    {
        return (string) ($this->setting('vendor_id') ?? '') !== '';
    }

    public function process(array $order, array $payment): array
    {
        $amount    = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $currency  = strtoupper((string) ($order['currency'] ?? $payment['currency'] ?? 'USD'));
        $reference = (string) ($payment['reference'] ?? '');
        $vendorId  = (string) ($this->setting('vendor_id') ?? '');

        if ($amount <= 0) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Invalid amount', 'raw' => null];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Paddle is not configured.', 'raw' => null];
        }

        $passthrough = rawurlencode(json_encode(['reference' => $reference]));

        // Paddle 2.0 checkout is a hosted URL; build it and redirect.
        $query = http_build_query([
            'vendor_id'    => $vendorId,
            'product_id'   => (string) ($order['product_id'] ?? ''),
            'quantity'     => 1,
            'price'        => number_format($amount, 2, '.', ''),
            'currency'     => $currency,
            'passthrough'  => $passthrough,
            'success_url'  => $order['return_url'] ?? '',
            'cancel_url'   => $order['cancel_url'] ?? '',
        ]);
        $checkoutUrl = ($this->setting('test_mode') ?? true) === true
            ? 'https://sandbox-checkout.paddle.com/custom-checkout/' . $vendorId . '?' . $query
            : 'https://checkout.paddle.com/custom-checkout/' . $vendorId . '?' . $query;

        return [
            'success'        => true,
            'transaction_id' => $reference,
            'message'        => 'Redirecting to Paddle checkout.',
            'redirect_url'   => $checkoutUrl,
            'raw'            => ['checkout_url' => $checkoutUrl],
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        // Paddle reports success via webhook (alert_name = payment_succeeded /
        // subscription_created). Inline verify just reflects current state.
        $ref = (string) ($data['reference'] ?? $payment['reference'] ?? '');

        return [
            'success'        => ($payment['status'] ?? '') === 'paid',
            'transaction_id' => $ref,
            'status'         => ($payment['status'] ?? 'pending'),
            'message'        => ($payment['status'] ?? '') === 'paid' ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => null,
        ];
    }

    /**
     * Verify a Paddle webhook payload (public-key based).
     */
    public function verifyWebhook(string $payload): bool
    {
        $vendorAuthCode = (string) ($this->setting('vendor_auth_code') ?? '');
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return false;
        }

        $signature = (string) ($decoded['p_signature'] ?? '');
        $keys = [];
        foreach ($decoded as $k => $v) {
            if ($k === 'p_signature') {
                continue;
            }
            $keys[] = $k . '=' . (is_array($v) ? json_encode($v) : $v);
        }
        sort($keys);
        $toSign = implode('|', $keys);

        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($vendorAuthCode), 64, "\n") . "-----END PUBLIC KEY-----";
        $ok = openssl_verify($toSign, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA1);

        return $ok === 1;
    }
}