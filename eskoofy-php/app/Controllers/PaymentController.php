<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class PaymentController extends Controller
{
    public function admissionPay(): void
    {
        $data = $this->validate([
            'admission_id' => 'required|numeric',
            'gateway'      => 'required',
        ]);

        $db = Database::getInstance();
        $admission = $db->fetch("SELECT * FROM admissions WHERE id = ?", [$data['admission_id']]);

        if (!$admission) {
            Session::getInstance()->flash('error', 'Admission not found.');
            $this->back();
            return;
        }

        $gateway = $db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? AND is_active = 1 LIMIT 1",
            [$data['gateway']]
        );

        if (!$gateway) {
            Session::getInstance()->flash('error', 'Payment gateway is not available.');
            $this->back();
            return;
        }

        $settings = $db->fetch("SELECT * FROM admission_settings ORDER BY id DESC LIMIT 1");
        $amount = (float) ($settings['admission_fee'] ?? 0);

        $paymentId = $db->insert('payments', [
            'paymentable_type' => 'admission',
            'paymentable_id'   => $admission['id'],
            'amount'           => $amount,
            'paid_amount'      => 0,
            'due_amount'       => $amount,
            'total_amount'     => $amount,
            'payment_method'   => $gateway['code'],
            'payment_status'   => 'pending',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $db->update('payments', [
            'invoice_number' => 'INV-' . str_pad((string) $paymentId, 8, '0', STR_PAD_LEFT),
        ], 'id = ?', [$paymentId]);

        if ($gateway['type'] === 'online') {
            $this->json([
                'success'   => true,
                'message'   => 'Payment initiated',
                'payment_id' => $paymentId,
                'gateway'   => $gateway['code'],
            ]);
        } else {
            Session::getInstance()->flash('success', 'Please complete the payment as instructed.');
            $this->redirect('/admission');
        }
    }

    public function admissionCallback(string $gateway): void
    {
        $db = Database::getInstance();

        $raw = file_get_contents('php://input');
        $params = $_GET;

        $paymentGateway = $db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? LIMIT 1",
            [$gateway]
        );

        if (!$paymentGateway) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown gateway']);
            return;
        }

        try {
            $paymentId = (int) ($params['payment_id'] ?? $params['invoice_number'] ?? 0);

            if ($paymentId > 0) {
                $payment = $db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);
                if ($payment) {
                    $status = 'completed';
                    if (isset($params['status'])) {
                        $status = $params['status'] === 'success' ? 'completed' : 'failed';
                    }

                    $db->update('payments', [
                        'payment_status' => $status,
                        'transaction_id' => $params['transaction_id'] ?? null,
                        'updated_at'     => date('Y-m-d H:i:s'),
                        'payment_date'   => $status === 'completed' ? date('Y-m-d H:i:s') : null,
                    ], 'id = ?', [$paymentId]);
                }

                $returnUrl = $payment['payment_details']['return_url'] ?? '/admission';
                $separator = str_contains($returnUrl, '?') ? '&' : '?';
                $this->redirect($returnUrl . $separator . http_build_query([
                    'payment_id' => $paymentId,
                    'status'     => $status,
                ]));
                return;
            }

            $this->redirect('/admission');
        } catch (\Throwable $e) {
            error_log("Payment callback error: " . $e->getMessage());
            $this->redirect('/admission');
        }
    }

    public function admissionWebhook(string $gateway): void
    {
        $db = Database::getInstance();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $paymentGateway = $db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? LIMIT 1",
            [$gateway]
        );

        if (!$paymentGateway) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unknown gateway']);
            return;
        }

        $hash = hash('sha256', $gateway . '|' . $raw);

        $existing = $db->fetch(
            "SELECT id, processed_at FROM payment_webhook_events WHERE payload_hash = ? LIMIT 1",
            [$hash]
        );

        if ($existing && $existing['processed_at']) {
            $this->json(['success' => true, 'message' => 'Duplicate webhook ignored']);
            return;
        }

        $eventId = null;
        if (!$existing) {
            $eventId = $db->insert('payment_webhook_events', [
                'gateway'      => $gateway,
                'payload_hash' => $hash,
                'payload'      => $raw,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } else {
            $eventId = $existing['id'];
        }

        try {
            $paymentId = (int) ($payload['payment_id'] ?? 0);
            if ($paymentId > 0) {
                $status = 'completed';
                if (isset($payload['status'])) {
                    $status = $payload['status'] === 'success' ? 'completed' : 'failed';
                }

                $db->update('payments', [
                    'payment_status' => $status,
                    'transaction_id' => $payload['transaction_id'] ?? null,
                    'updated_at'     => date('Y-m-d H:i:s'),
                    'payment_date'   => $status === 'completed' ? date('Y-m-d H:i:s') : null,
                ], 'id = ?', [$paymentId]);
            }

            if ($eventId) {
                $db->update('payment_webhook_events', [
                    'processed_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$eventId]);
            }

            $this->json(['success' => true, 'message' => 'Webhook processed']);
        } catch (\Throwable $e) {
            error_log("Webhook processing error: " . $e->getMessage());
            if ($eventId) {
                $db->update('payment_webhook_events', [
                    'processed_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$eventId]);
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Webhook processing failed']);
            exit;
        }
    }
}
