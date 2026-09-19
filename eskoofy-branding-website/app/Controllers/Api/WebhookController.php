<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Gateways\GatewayFactory;
use App\Services\LicenseManager;

/**
 * Gateway webhooks for subscription auto-renewal (international gateways:
 * Stripe, PayPal, Paddle). Signature-verified; on a successful renewal event
 * the matching subscription + license are extended.
 *
 * BD gateways (bKash/Rocket/Nagad) renew manually, so they don't post here.
 */
class WebhookController extends Controller
{
    public function handle(string $gateway): void
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw !== false ? $raw : '', true) ?? $_POST;

        if (!is_array($payload)) {
            $payload = [];
        }

        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE']
            ?? $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG']
            ?? $_SERVER['HTTP_X_SIGNATURE']
            ?? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE']
            ?? '';

        $gatewayService = GatewayFactory::make($gateway);

        // Signature verification (gateway-specific).
        if (method_exists($gatewayService, 'verifyWebhook')) {
            $verified = $gatewayService->verifyWebhook((string) $raw, $signature);
        } elseif (method_exists($gatewayService, 'verifyWebhookPayload')) {
            $verified = $gatewayService->verifyWebhookPayload((string) $raw);
        } else {
            $verified = false;
        }

        if (!$verified) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Invalid webhook signature']);
        }

        $this->dispatch($gateway, $payload);

        http_response_code(200);
        $this->json(['success' => true, 'message' => 'Webhook processed successfully']);
    }

    private function dispatch(string $gateway, array $payload): void
    {
        switch ($gateway) {
            case 'stripe':
                $this->handleStripe($payload);
                break;
            case 'paypal':
                $this->handlePaypal($payload);
                break;
            case 'paddle':
                $this->handlePaddle($payload);
                break;
            default:
                // Manual renewals; nothing to do.
                break;
        }
    }

    private function referenceFor(array $payload): ?string
    {
        foreach (['reference', 'reference_id', 'merchant_inv_no', 'merchantInvoiceNumber', 'order_id'] as $key) {
            if (!empty($payload[$key])) {
                return (string) $payload[$key];
            }
        }
        // Nested passthrough (Paddle) / metadata (Stripe).
        if (!empty($payload['passthrough'])) {
            $decoded = json_decode((string) $payload['passthrough'], true);
            if (is_array($decoded) && !empty($decoded['reference'])) {
                return (string) $decoded['reference'];
            }
        }
        if (!empty($payload['data']) && is_array($payload['data'])) {
            return $this->referenceFor($payload['data']);
        }
        return null;
    }

    private function extendByReference(?string $reference, string $gateway): void
    {
        if ($reference === null) {
            return;
        }
        $db = Database::getInstance();
        $payment = $db->fetch(
            "SELECT * FROM payments WHERE reference = ? ORDER BY id DESC LIMIT 1",
            [$reference]
        );
        if (!$payment || empty($payment['license_id']) || empty($payment['plan_id'])) {
            return;
        }

        $manager = new LicenseManager();
        $manager->createSubscription(
            (int) $payment['license_id'],
            (int) $payment['plan_id'],
            $gateway,
            ['customer_id' => (int) $payment['customer_id']]
        );
    }

    private function handleStripe(array $payload): void
    {
        $type = (string) ($payload['type'] ?? '');
        if ($type === 'checkout.session.completed' || $type === 'invoice.paid') {
            $this->extendByReference($this->referenceFor($payload), 'stripe');
        }
    }

    private function handlePaypal(array $payload): void
    {
        $eventType = (string) ($payload['event_type'] ?? '');
        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' || $eventType === 'BILLING.SUBSCRIPTION.ACTIVATED') {
            $this->extendByReference($this->referenceFor($payload), 'paypal');
        }
    }

    private function handlePaddle(array $payload): void
    {
        $alert = (string) ($payload['alert_name'] ?? '');
        if (in_array($alert, ['payment_succeeded', 'subscription_created', 'subscription_updated'], true)) {
            $this->extendByReference($this->referenceFor($payload), 'paddle');
        }
    }
}