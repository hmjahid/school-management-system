<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use Illuminate\Http\Request;

trait VerifiesWebhookSignature
{
    /**
     * Verify an incoming webhook/IPN signature using HMAC-SHA256 over the raw
     * request body with the gateway's configured secret.
     *
     * This is fail-closed by design: it returns false (a) when no secret is
     * configured for the gateway, or (b) when the signature header is missing
     * or empty, or (c) when the computed signature does not match exactly.
     * There is intentionally NO "optional / off by default" bypass path.
     *
     * Concrete provider algorithms (approximated with HMAC, which is what the
     * gateway signs with on the server side):
     *  - bKash:  HMAC-SHA256 of the raw body, signed with the app secret, sent
     *            in the `signature` / `X-Bkash-Signature` header.
     *  - Nagad:  HMAC-SHA256 of the raw body, signed with the merchant secret,
     *            sent in the `X-Nagad-Signature` header (provider also encrypts
     *            a `signature` claim — production should additionally verify the
     *            decrypted claim against the merchant public key).
     *  - Rocket: HMAC-SHA256 of the raw body, signed with the merchant secret,
     *            sent in the `X-Rocket-Signature` header.
     */
    protected function verifyHmacSignature(Request $request, PaymentGateway $gateway, string $header): bool
    {
        $secret = $gateway->api_secret;

        if (empty($secret)) {
            return false;
        }

        $provided = (string) (
            $request->header($header)
            ?? $request->header('signature')
            ?? $request->header('X-Webhook-Signature')
            ?? ''
        );

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac('sha256', (string) $request->getContent(), (string) $secret);

        return hash_equals($expected, $provided);
    }
}
