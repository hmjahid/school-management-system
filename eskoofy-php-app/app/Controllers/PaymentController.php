<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Gateways\GatewayFactory;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentGateway;
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

        $invoiceNumber = generate_invoice_number();

        $paymentId = $db->insert('payments', [
            'paymentable_type' => 'tuition',
            'paymentable_id'   => $feePaymentId,
            'invoice_number'   => $invoiceNumber,
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

        if ((int) ($gateway['is_online'] ?? 0) === 1) {
            try {
                $adapter = GatewayFactory::makeFromPaymentRecord([
                    'payment_method' => $gateway['code'],
                    'amount'         => $amount,
                ], $gateway);

                $init = $adapter->initialize([
                    'amount'      => $amount,
                    'invoice_id'  => $invoiceNumber,
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
     * Render the local sandbox page for the zero-credential test gateway.
     * Mirrors the app's PaymentSandboxController::show.
     */
    public function sandbox(?int $payment = null): void
    {
        if ($payment === null) {
            $demo = $this->demoPayment();
            $this->redirect('/payments/sandbox/' . (int) $demo->id);
            return;
        }

        $model = Payment::find($payment);
        if (!$model) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        if (!$this->guardSandbox($model)) {
            return;
        }

        $gateway = PaymentGateway::query()
            ->where('code', PaymentGateway::GATEWAY_TEST_GATEWAY)
            ->first();

        if (!$gateway) {
            Session::getInstance()->flash('error', __('Payment gateway is not available.'));
            $this->redirect('/payments');
            return;
        }

        $this->view('payments.sandbox', [
            'payment' => $model,
            'gateway' => $gateway,
        ]);
    }

    /**
     * Apply a simulated sandbox outcome (success|failure|cancel).
     * Mirrors the app's PaymentSandboxController::simulate.
     */
    public function simulate(int $payment): void
    {
        $model = Payment::find($payment);
        if (!$model) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        if (!$this->guardSandbox($model)) {
            return;
        }

        $simulate = (string) ($_POST['simulate'] ?? '');
        if (!in_array($simulate, ['success', 'failure', 'cancel'], true)) {
            Session::getInstance()->flash('error', __('Invalid sandbox outcome.'));
            $this->redirect('/payments/sandbox/' . $payment);
            return;
        }

        $db = Database::getInstance();
        $invoice = (string) $model->invoice_number;

        if ($simulate === 'success') {
            if ($model->payment_status !== Payment::STATUS_COMPLETED) {
                $db->update('payments', [
                    'payment_status' => Payment::STATUS_COMPLETED,
                    'transaction_id' => 'TEST-' . $invoice,
                    'paid_amount'    => (float) $model->total_amount,
                    'due_amount'     => 0,
                    'payment_date'   => date('Y-m-d'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ], 'id = ?', [$payment]);

                $this->markLinkedFeePaymentPaid($db, $model->toArray());
            }

            Session::getInstance()->flash('success', __('Test payment completed. No money moved.'));
        } else {
            $status = $simulate === 'cancel' ? Payment::STATUS_CANCELLED : Payment::STATUS_FAILED;

            if ($model->payment_status !== Payment::STATUS_COMPLETED && $model->payment_status !== $status) {
                $db->update('payments', [
                    'payment_status' => $status,
                    'updated_at'     => date('Y-m-d H:i:s'),
                ], 'id = ?', [$payment]);
            }

            Session::getInstance()->flash(
                'error',
                $simulate === 'cancel' ? __('Test payment cancelled.') : __('Test payment marked as failed.')
            );
        }

        $this->redirect($this->sandboxDestination(Payment::find($payment)));
    }

    /**
     * Create a throwaway payment used when the sandbox is opened without one.
     */
    private function demoPayment(): Payment
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');

        $id = $db->insert('payments', [
            'paymentable_type' => Payment::PURPOSE_OTHER,
            'paymentable_id'   => 0,
            'invoice_number'   => generate_invoice_number('INV'),
            'amount'           => 100,
            'paid_amount'      => 0,
            'due_amount'       => 100,
            'discount_amount'  => 0,
            'fine_amount'      => 0,
            'tax_amount'       => 0,
            'total_amount'     => 100,
            'payment_method'   => PaymentGateway::GATEWAY_TEST_GATEWAY,
            'payment_status'   => Payment::STATUS_PENDING,
            'payment_details'  => json_encode(['description' => 'Sandbox test payment']),
            'metadata'         => json_encode(['sandbox' => true]),
            'created_by'       => Auth::id(),
            'updated_by'       => Auth::id(),
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        return Payment::find((int) $id);
    }

    /**
     * Only the owner (or an admin) may drive a payment's sandbox page, and only
     * when the payment actually uses the test gateway.
     */
    private function guardSandbox(Payment $payment): bool
    {
        $role = Auth::role();
        $isAdmin = in_array($role, ['admin', 'super_admin', 'owner', 'Administrator'], true);

        if ((!$isAdmin && (int) $payment->created_by !== (int) Auth::id())
            || $payment->payment_method !== PaymentGateway::GATEWAY_TEST_GATEWAY) {
            http_response_code(403);
            $this->view('errors.403');
            return false;
        }

        return true;
    }

    private function sandboxDestination(?Payment $payment): string
    {
        if (!$payment) {
            return '/payments';
        }

        $details = is_array($payment->payment_details) ? $payment->payment_details : [];
        $key = $payment->payment_status === Payment::STATUS_COMPLETED ? 'return_url' : 'cancel_url';
        $url = (string) ($details[$key] ?? $details['return_url'] ?? '');

        if ($url === '' || str_contains($url, '__PAYMENT__')) {
            return '/payments/sandbox/' . (int) $payment->id;
        }

        return $url;
    }

    /**
     * Mark the linked fee payment paid so the receipt surface appears.
     *
     * @param array<string, mixed> $payment
     */
    private function markLinkedFeePaymentPaid(DatabaseInterface $db, array $payment): void
    {
        $metadata = $payment['metadata'] ?? null;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $feePaymentId = (int) ($metadata['fee_payment_id'] ?? 0);
        if ($feePaymentId <= 0) {
            return;
        }

        $fee = $db->fetch("SELECT * FROM fee_payments WHERE id = ? LIMIT 1", [$feePaymentId]);
        if (!$fee || (string) $fee['status'] === 'paid') {
            return;
        }

        $db->update('fee_payments', [
            'status'      => 'paid',
            'paid_amount' => $fee['amount'],
            'balance'     => 0,
            'updated_at'  => date('Y-m-d H:i:s'),
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => Auth::id(),
        ], 'id = ?', [$feePaymentId]);
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

        if ((int) ($gateway['is_online'] ?? 0) === 1) {
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