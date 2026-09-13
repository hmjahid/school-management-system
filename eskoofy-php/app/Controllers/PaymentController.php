<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Gateways\GatewayFactory;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;

class PaymentController extends Controller
{
    public function initiate(): void
    {
        if (!Auth::check()) {
            Session::getInstance()->flash('error', __('Please login to continue.'));
            $this->redirect('/login');
        }

        $user = Auth::user();
        $studentIds = $this->studentIdsForUser($user);

        if ($studentIds === []) {
            Session::getInstance()->flash('error', __('No student account is linked to your profile.'));
            $this->redirect('/payments');
        }

        $data = $this->validate([
            'student_id' => 'required|numeric',
            'fee_id'     => 'required|numeric',
            'gateway'    => 'required',
            'amount'     => 'required|numeric|min:1',
        ]);

        $studentId = (int) $data['student_id'];
        if (!in_array($studentId, $studentIds, true)) {
            Session::getInstance()->flash('error', __('Invalid student selection.'));
            $this->redirect('/payments');
        }

        $db = Database::getInstance();

        $fee = $db->fetch("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL LIMIT 1", [$data['fee_id']]);
        if (!$fee) {
            Session::getInstance()->flash('error', __('Fee not found.'));
            $this->redirect('/payments');
        }

        $gateway = $db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? AND is_active = 1 LIMIT 1",
            [$data['gateway']]
        );
        if (!$gateway) {
            Session::getInstance()->flash('error', __('Payment gateway is not available.'));
            $this->redirect('/payments');
        }

        $amount = (float) $data['amount'];

        $feePaymentId = $db->insert('fee_payments', [
            'student_id'     => $studentId,
            'fee_id'         => (int) $fee['id'],
            'amount'         => $amount,
            'paid_amount'    => 0,
            'discount_amount'=> 0,
            'fine_amount'    => 0,
            'balance'        => $amount,
            'payment_date'   => date('Y-m-d'),
            'payment_method' => 'online_payment',
            'status'         => 'pending',
            'metadata'       => json_encode(['gateway' => $gateway['code']]),
            'created_by'     => Auth::id(),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $paymentId = $db->insert('payments', [
            'paymentable_type' => 'tuition',
            'paymentable_id'   => $feePaymentId,
            'amount'           => $amount,
            'paid_amount'      => 0,
            'due_amount'       => $amount,
            'discount_amount'  => 0,
            'fine_amount'      => 0,
            'tax_amount'       => 0,
            'total_amount'     => $amount,
            'payment_method'   => $gateway['code'],
            'payment_status'   => 'pending',
            'payment_details'  => json_encode([
                'description' => 'Fee payment: ' . ($fee['name'] ?? 'Fee'),
                'return_url'  => '/payments/status/' . $paymentId,
                'cancel_url'  => '/payments/status/' . $paymentId,
            ]),
            'metadata'         => json_encode([
                'fee_payment_id' => $feePaymentId,
                'student_id'     => $studentId,
                'fee_id'         => (int) $fee['id'],
            ]),
            'created_by'       => Auth::id(),
            'updated_by'       => Auth::id(),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $description = 'Fee payment: ' . ($fee['name'] ?? 'Fee');
        $db->update('payments', [
            'payment_details' => json_encode([
                'description' => $description,
                'return_url'  => '/payments/status/' . $paymentId,
                'cancel_url'  => '/payments/status/' . $paymentId,
            ]),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$paymentId]);

        if ($gateway['type'] === 'online') {
            try {
                $adapter = GatewayFactory::makeFromPaymentRecord([
                    'payment_method' => $gateway['code'],
                    'amount'         => $amount,
                ], $gateway);

                $init = $adapter->initialize([
                    'amount'      => $amount,
                    'invoice_id'  => 'INV-' . str_pad((string) $paymentId, 8, '0', STR_PAD_LEFT),
                    'payment_id'  => $paymentId,
                    'student_id'  => $studentId,
                    'currency'    => config('payment.currency', 'BDT'),
                    'description' => $description,
                ]);

                if (!$init['success']) {
                    Session::getInstance()->flash('error', $init['message'] ?? 'Payment initiation failed.');
                    $this->redirect('/payments');
                }

                $redirectUrl = $init['data']['payment_url'] ?? ($init['data']['client_secret'] ?? '');

                $details = [
                    'description' => $description,
                    'return_url'  => '/payments/status/' . $paymentId,
                    'cancel_url'  => '/payments/status/' . $paymentId,
                ];
                foreach (($init['data'] ?? []) as $k => $v) {
                    $details[$k] = $v;
                }
                $db->update('payments', [
                    'reference_number' => $init['data']['payment_id'] ?? null,
                    'payment_details'  => json_encode($details),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ], 'id = ?', [$paymentId]);

                if ($redirectUrl !== '') {
                    $this->redirect($redirectUrl);
                }
            } catch (\Throwable $e) {
                error_log('Payment init failed: ' . $e->getMessage());
                Session::getInstance()->flash('error', 'Payment gateway error: ' . $e->getMessage());
                $this->redirect('/payments');
            }
        }

        Session::getInstance()->flash('success', __('Payment initiated. Please complete the payment as instructed.'));
        $this->redirect('/payments/status/' . $paymentId);
    }

    /**
     * Map the current user to the student record(s) they can pay for
     * (mirrors PaymentsWebController::studentIdsForUser).
     *
     * @return list<int>
     */
    private function studentIdsForUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        if (Auth::hasRole('student')) {
            try {
                $id = (int) Student::query()->where('user_id', $user->id)->value('id');
                return $id > 0 ? [$id] : [];
            } catch (\Throwable) {
                return [];
            }
        }

        if (Auth::hasRole('parent')) {
            try {
                $guardian = Guardian::query()->where('user_id', $user->id)->first();
                if (!$guardian) {
                    return [];
                }
                return array_values(array_filter(Student::query()->where('guardian_id', $guardian->id)->pluck('id'), fn ($v) => $v !== null));
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }

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

        $invoiceNumber = 'INV-' . str_pad((string) $paymentId, 8, '0', STR_PAD_LEFT);
        $db->update('payments', [
            'invoice_number' => $invoiceNumber,
        ], 'id = ?', [$paymentId]);

        if ($gateway['type'] === 'online') {
            try {
                $adapter = GatewayFactory::make($gateway['code']);

                $gatewayCfg = $db->fetch(
                    "SELECT * FROM payment_gateways WHERE code = ? LIMIT 1",
                    [$gateway['code']]
                );
                $gatewayConfig = $gatewayCfg ?: [];

                $adapter = GatewayFactory::makeFromPaymentRecord([
                    'payment_method' => $gateway['code'],
                    'amount'         => $amount,
                ], $gatewayConfig);

                $init = $adapter->initialize([
                    'amount'     => $amount,
                    'invoice_id' => $invoiceNumber,
                    'payment_id' => $paymentId,
                    'student_id' => $admission['id'],
                    'currency'   => config('payment.currency', 'BDT'),
                    'description'=> 'Admission fee for application #' . $admission['application_number'],
                ]);

                if (!$init['success']) {
                    Session::getInstance()->flash('error', $init['message'] ?? 'Payment initiation failed.');
                    $this->back();
                    return;
                }

                $redirectUrl = $init['data']['payment_url'] ?? '';
                if ($redirectUrl === '') {
                    $redirectUrl = $init['data']['client_secret'] ?? '';
                }

                $db->update('payments', [
                    'reference_number' => $init['data']['payment_id'] ?? null,
                    'payment_details'  => json_encode($init['data'] ?? []),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ], 'id = ?', [$paymentId]);

                if ($redirectUrl !== '') {
                    $this->redirect($redirectUrl);
                }

                $this->json([
                    'success'   => true,
                    'message'   => 'Payment initiated',
                    'payment_id'=> $paymentId,
                    'gateway'   => $gateway['code'],
                    'data'      => $init['data'],
                    'redirect_url' => $init['data']['payment_url'] ?? null,
                ]);
            } catch (\Throwable $e) {
                error_log('Payment init failed: ' . $e->getMessage());
                Session::getInstance()->flash('error', 'Payment gateway error: ' . $e->getMessage());
                $this->back();
                return;
            }
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
            $adapter = GatewayFactory::makeFromPaymentRecord(
                ['payment_method' => $gateway],
                $paymentGateway
            );

            $payload = array_merge($params);
            if ($raw !== '' && $raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = array_merge($payload, $decoded);
                }
            }

            $result = $adapter->processCallback($payload);

            $paymentId = (int) ($params['payment_id'] ?? $params['invoice_number'] ?? $result['data']['invoice_id'] ?? 0);

            if ($paymentId <= 0 && isset($result['data']['payment_id'])) {
                $paymentId = (int) $result['data']['payment_id'];
            }

            $status = $result['success'] ? 'completed' : 'failed';

            if ($paymentId > 0) {
                $payment = $db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);
                if ($payment) {
                    $db->update('payments', [
                        'payment_status' => $status,
                        'transaction_id' => $result['data']['transaction_id'] ?? $params['transaction_id'] ?? null,
                        'updated_at'     => date('Y-m-d H:i:s'),
                        'payment_date'   => $status === 'completed' ? date('Y-m-d H:i:s') : null,
                    ], 'id = ?', [$paymentId]);

                    $returnUrl = $payment['payment_details'] ?? '';
                    if (is_array($payment['payment_details'])) {
                        $returnUrl = $payment['payment_details']['return_url'] ?? '/admission';
                    }
                    if (!is_string($returnUrl) || $returnUrl === '') {
                        $returnUrl = '/admission';
                    }
                } else {
                    $returnUrl = '/admission';
                }
            } else {
                $returnUrl = '/admission';
            }

            $separator = str_contains($returnUrl, '?') ? '&' : '?';
            $this->redirect($returnUrl . $separator . http_build_query([
                'payment_id' => $paymentId,
                'status'     => $status,
            ]));
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
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_X_BKASH_SIGNATURE'] ?? '';

        $paymentGateway = $db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? LIMIT 1",
            [$gateway]
        );

        if (!$paymentGateway) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unknown gateway']);
            return;
        }

        try {
            $adapter = GatewayFactory::makeFromPaymentRecord(
                ['payment_method' => $gateway],
                $paymentGateway
            );

            if ($signature !== '' && !$adapter->verifyWebhookSignature((string) $raw, $signature)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid webhook signature']);
                exit;
            }

            $hash = hash('sha256', $gateway . '|' . (string) $raw);

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
                    'payload'      => (string) $raw,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            } else {
                $eventId = $existing['id'];
            }

            try {
                $result = $adapter->processCallback($payload);

                $paymentId = (int) ($payload['payment_id'] ?? $result['data']['invoice_id'] ?? 0);
                if ($paymentId > 0) {
                    $status = $result['success'] ? 'completed' : 'failed';
                    $db->update('payments', [
                        'payment_status' => $status,
                        'transaction_id' => $result['data']['transaction_id'] ?? $payload['transaction_id'] ?? null,
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
        } catch (\Throwable $e) {
            error_log("Webhook verification error: " . $e->getMessage());
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Webhook verification failed']);
            exit;
        }
    }
}