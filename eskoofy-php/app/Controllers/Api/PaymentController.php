<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Schema;
use App\Core\Validator;
use App\Gateways\GatewayFactory;

class PaymentController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function gateways(): void
    {
        $gateways = [];
        if (Schema::hasTable('payment_gateways')) {
            $rows = $this->db->fetchAll(
                "SELECT id, name, code, type, is_active, is_online, has_api, test_mode,
                        logo, description, currency, fee_percentage, fee_fixed,
                        min_amount, max_amount, supported_currencies, sort_order
                 FROM payment_gateways
                 WHERE is_active = 1 AND deleted_at IS NULL
                 ORDER BY sort_order ASC, name ASC"
            );
            foreach ($rows as $row) {
                $gateways[] = [
                    'id'                    => (int) $row['id'],
                    'name'                  => $row['name'],
                    'code'                  => $row['code'],
                    'type'                  => $row['type'],
                    'is_online'             => (bool) $row['is_online'],
                    'logo_url'              => $row['logo'] ? url('uploads/' . ltrim($row['logo'], '/')) : null,
                    'description'           => $row['description'],
                    'fee_percentage'        => (float) $row['fee_percentage'],
                    'fee_fixed'             => (float) $row['fee_fixed'],
                    'min_amount'            => $row['min_amount'] !== null ? (float) $row['min_amount'] : null,
                    'max_amount'            => $row['max_amount'] !== null ? (float) $row['max_amount'] : null,
                    'currency'              => $row['currency'],
                    'supported_currencies'  => $this->decodeJson($row['supported_currencies'], [$row['currency']]),
                    'is_configured'         => $this->gatewayIsConfigured($row),
                ];
            }
        }

        if ($gateways === []) {
            foreach (GatewayFactory::all() as $code => $name) {
                $gateways[] = [
                    'id'                   => null,
                    'name'                 => $name,
                    'code'                 => $code,
                    'type'                 => $code === 'offline' ? 'bank' : 'online_payment',
                    'is_online'            => $code !== 'offline',
                    'logo_url'             => null,
                    'description'          => null,
                    'fee_percentage'       => 0.0,
                    'fee_fixed'            => 0.0,
                    'min_amount'           => null,
                    'max_amount'           => null,
                    'currency'             => config('payment.currency', 'BDT'),
                    'supported_currencies' => [config('payment.currency', 'BDT')],
                    'is_configured'        => true,
                ];
            }
        }

        $this->success($gateways, 'Payment gateways retrieved');
    }

    public function initiate(): void
    {
        $data = $this->validateBody($this->body(), [
            'gateway'           => 'required|max:50',
            'amount'            => 'required|numeric',
            'currency'          => 'required|max:3',
            'paymentable_type'  => 'required|max:191',
            'paymentable_id'    => 'required|numeric',
            'description'       => 'max:255',
            'return_url'        => 'max:255',
            'cancel_url'        => 'max:255',
            'metadata'          => '',
        ]);

        $gateway = $this->db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
            [$data['gateway']]
        );

        if (!$gateway) {
            $this->error('The selected payment gateway is currently unavailable.', 400);
        }

        if (!$this->isActive($gateway)) {
            $this->error('The selected payment gateway is currently unavailable.', 400);
        }

        if ($this->isOnline($gateway) && !$this->gatewayIsConfigured($gateway)) {
            $this->error('The selected payment gateway is not properly configured.', 400);
        }

        $amount = (float) $data['amount'];
        $currency = $data['currency'];
        $supported = $this->decodeJson($gateway['supported_currencies'], [$gateway['currency']]);

        if ($gateway['min_amount'] !== null && $amount < (float) $gateway['min_amount']) {
            $this->error("Minimum payment amount is {$gateway['currency']} {$gateway['min_amount']} for {$gateway['name']}.", 400);
        }
        if ($gateway['max_amount'] !== null && $amount > (float) $gateway['max_amount']) {
            $this->error("Maximum payment amount is {$gateway['currency']} {$gateway['max_amount']} for {$gateway['name']}.", 400);
        }
        if (!in_array($currency, $supported, true)) {
            $this->error("The selected currency is not supported by {$gateway['name']}.", 400);
        }

        $fee = (float) $gateway['fee_fixed'] + ($amount * (float) $gateway['fee_percentage'] / 100);
        $totalAmount = round($amount + $fee, 2);

        $userId = \App\Core\Auth::id();
        $tempInvoice = 'INV-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $paymentId = $this->db->insert('payments', [
            'paymentable_type' => $data['paymentable_type'],
            'paymentable_id'   => (int) $data['paymentable_id'],
            'invoice_number'   => $tempInvoice,
            'amount'           => $amount,
            'paid_amount'      => 0,
            'due_amount'       => $totalAmount,
            'discount_amount'  => 0,
            'fine_amount'      => 0,
            'tax_amount'       => 0,
            'total_amount'     => $totalAmount,
            'payment_method'   => $gateway['code'],
            'payment_status'   => 'pending',
            'refund_status'    => 'not_refunded',
            'payment_details'  => json_encode([
                'description'    => $data['description'] ?? null,
                'metadata'       => $data['metadata'] ?? null,
                'fee_percentage' => (float) $gateway['fee_percentage'],
                'fee_fixed'      => (float) $gateway['fee_fixed'],
                'fee_amount'     => $fee,
                'return_url'     => $data['return_url'] ?? null,
                'cancel_url'     => $data['cancel_url'] ?? null,
            ]),
            'notes'      => $data['description'] ?? null,
            'metadata'   => ($data['metadata'] ?? null) !== null ? json_encode($data['metadata']) : null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->update('payments', [
            'invoice_number' => 'INV-' . str_pad((string) $paymentId, 8, '0', STR_PAD_LEFT),
        ], 'id = ?', [$paymentId]);

        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);

        $gatewayResult = ['success' => false, 'message' => 'Offline instructions', 'data' => []];

        if ($this->isOnline($gateway) && $this->gatewayHasApi($gateway)) {
            try {
                $adapter = GatewayFactory::makeFromPaymentRecord([
                    'payment_method' => $gateway['code'],
                    'amount'         => $amount,
                ], $gateway);

                $init = $adapter->initialize([
                    'amount'      => $amount,
                    'invoice_id'  => $payment['invoice_number'],
                    'payment_id'  => $paymentId,
                    'student_id'  => (int) $data['paymentable_id'],
                    'currency'    => $currency,
                    'description' => $data['description'] ?? 'Payment',
                ]);

                if ($init['success']) {
                    $gatewayResult = $init;
                    $details = json_decode($payment['payment_details'] ?? '[]', true) ?: [];
                    foreach (($init['data'] ?? []) as $k => $v) {
                        $details[$k] = $v;
                    }
                    $this->db->update('payments', [
                        'reference_number' => $init['data']['payment_id'] ?? null,
                        'payment_details'  => json_encode($details),
                        'updated_at'       => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$paymentId]);
                    $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);
                } else {
                    $gatewayResult = $init;
                }
            } catch (\Throwable $e) {
                $this->error('Failed to initiate payment. Please try again or contact support.', 500, ['error' => $e->getMessage()]);
            }
        }

        $this->success([
            'payment' => $this->presentPayment($payment),
            'gateway' => $gatewayResult,
        ], 'Payment initiated successfully');
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 15)));
        $offset = ($page - 1) * $perPage;

        $where = 'p.deleted_at IS NULL';
        $params = [];

        if (($_GET['status'] ?? '') !== '') {
            $where .= ' AND p.payment_status = ?';
            $params[] = $_GET['status'];
        }
        if (($_GET['gateway'] ?? '') !== '') {
            $where .= ' AND p.payment_method = ?';
            $params[] = $_GET['gateway'];
        }
        if (($_GET['paymentable_type'] ?? '') !== '') {
            $where .= ' AND p.paymentable_type = ?';
            $params[] = $_GET['paymentable_type'];
        }
        if (($_GET['paymentable_id'] ?? '') !== '') {
            $where .= ' AND p.paymentable_id = ?';
            $params[] = (int) $_GET['paymentable_id'];
        }
        if (($_GET['search'] ?? '') !== '') {
            $search = (string) $_GET['search'];
            $where .= " AND (p.invoice_number LIKE ? OR p.reference_number LIKE ? OR p.transaction_id LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (($_GET['date_from'] ?? '') !== '' && ($_GET['date_to'] ?? '') !== '') {
            $where .= ' AND p.created_at BETWEEN ? AND ?';
            $params[] = $_GET['date_from'] . ' 00:00:00';
            $params[] = $_GET['date_to'] . ' 23:59:59';
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM payments p WHERE {$where}", $params
        )['cnt'] ?? 0);

        $sortBy = in_array($_GET['sort_by'] ?? '', ['created_at', 'updated_at', 'payment_date', 'amount'], true)
            ? $_GET['sort_by'] : 'created_at';
        $sortOrder = strtolower($_GET['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $rows = $this->db->fetchAll(
            "SELECT p.*, u.name as created_by_name
             FROM payments p
             LEFT JOIN users u ON p.created_by = u.id
             WHERE {$where}
             ORDER BY p.{$sortBy} {$sortOrder}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->paginated([
            'data'         => array_map(fn ($row) => $this->presentPayment($row), $rows),
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function show(int $id): void
    {
        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$id]);
        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        $this->success($this->presentPayment($payment), 'Payment retrieved');
    }

    public function status(int|string $id): void
    {
        if (is_numeric($id)) {
            $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [(int) $id]);
        } else {
            $payment = $this->db->fetch("SELECT * FROM payments WHERE invoice_number = ? LIMIT 1", [$id]);
        }

        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        if (!in_array($payment['payment_status'], ['completed', 'refunded'], true)) {
            $this->verifyWithGateway($payment);
            $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$payment['id']]);
        }

        $this->success($this->presentPayment($payment), 'Payment status retrieved');
    }

    public function recordOffline(): void
    {
        $data = $this->validateBody($this->body(), [
            'gateway'           => 'required|max:50',
            'amount'            => 'required|numeric',
            'currency'          => 'required|max:3',
            'paymentable_type'  => 'required|max:191',
            'paymentable_id'    => 'required|numeric',
            'payment_date'      => 'required|date',
            'reference_number'  => 'max:100',
            'transaction_id'    => 'max:100',
            'description'       => 'max:255',
            'notes'             => 'max:1000',
        ]);

        $gateway = $this->db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
            [$data['gateway']]
        );

        if (!$gateway) {
            $this->error('The selected payment gateway is currently unavailable.', 400);
        }

        $amount = (float) $data['amount'];
        $userId = \App\Core\Auth::id();
        $tempInvoice = 'INV-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $paymentId = $this->db->insert('payments', [
            'paymentable_type' => $data['paymentable_type'],
            'paymentable_id'   => (int) $data['paymentable_id'],
            'invoice_number'   => $tempInvoice,
            'amount'           => $amount,
            'paid_amount'      => $amount,
            'due_amount'       => 0,
            'discount_amount'  => 0,
            'fine_amount'      => 0,
            'tax_amount'       => 0,
            'total_amount'     => $amount,
            'payment_method'   => $gateway['code'],
            'payment_status'   => 'completed',
            'refund_status'    => 'not_refunded',
            'payment_date'     => $data['payment_date'],
            'reference_number' => $data['reference_number'] ?? null,
            'transaction_id'   => $data['transaction_id'] ?? null,
            'payment_details'  => json_encode([
                'description' => $data['description'] ?? null,
                'metadata'    => $data['metadata'] ?? null,
                'recorded_by' => $userId,
                'recorded_at' => date('Y-m-d H:i:s'),
            ]),
            'notes'      => $data['notes'] ?? null,
            'metadata'   => isset($data['metadata']) && is_array($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->update('payments', [
            'invoice_number' => 'INV-' . str_pad((string) $paymentId, 8, '0', STR_PAD_LEFT),
        ], 'id = ?', [$paymentId]);

        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);

        $this->success($this->presentPayment($payment), 'Offline payment recorded successfully', 201);
    }

    public function updateStatus(int $id): void
    {
        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$id]);
        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        $data = $this->validateBody($this->body(), [
            'status' => 'required|in:pending,processing,completed,failed,refunded,cancelled,expired',
            'notes'  => 'max:1000',
        ]);

        $status = $data['status'];
        $updates = [
            'payment_status' => $status,
            'notes'          => $data['notes'] ?? $payment['notes'],
            'updated_by'     => \App\Core\Auth::id(),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($status === 'completed') {
            $updates['paid_amount'] = (float) $payment['total_amount'];
            $updates['due_amount'] = 0;
            $updates['payment_date'] = $payment['payment_date'] ?? date('Y-m-d');
        }

        $this->db->update('payments', $updates, 'id = ?', [$id]);

        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$id]);

        $this->success($this->presentPayment($payment), 'Payment status updated');
    }

    public function export(): void
    {
        $where = 'p.deleted_at IS NULL';
        $params = [];
        if (($_GET['status'] ?? '') !== '') {
            $where .= ' AND p.payment_status = ?';
            $params[] = $_GET['status'];
        }
        if (($_GET['gateway'] ?? '') !== '') {
            $where .= ' AND p.payment_method = ?';
            $params[] = $_GET['gateway'];
        }
        if (($_GET['start_date'] ?? '') !== '' && ($_GET['end_date'] ?? '') !== '') {
            $where .= ' AND p.created_at BETWEEN ? AND ?';
            $params[] = $_GET['start_date'] . ' 00:00:00';
            $params[] = $_GET['end_date'] . ' 23:59:59';
        }

        $rows = $this->db->fetchAll(
            "SELECT p.*, u.name as created_by_name
             FROM payments p
             LEFT JOIN users u ON p.created_by = u.id
             WHERE {$where}
             ORDER BY p.created_at DESC",
            $params
        );

        $data = [];
        foreach ($rows as $payment) {
            $details = $this->decodeJson($payment['payment_details'], []);
            $data[] = [
                'Invoice Number'  => $payment['invoice_number'],
                'Date'            => $payment['created_at'],
                'Payment Method'  => $payment['payment_method'],
                'Status'          => $payment['payment_status'],
                'Amount'          => (float) $payment['amount'],
                'Fee'             => round((float) $payment['total_amount'] - (float) $payment['amount'], 2),
                'Total'           => (float) $payment['total_amount'],
                'Paid'            => (float) $payment['paid_amount'],
                'Due'             => (float) $payment['due_amount'],
                'Currency'        => 'BDT',
                'Reference'       => $payment['reference_number'],
                'Transaction ID'  => $payment['transaction_id'],
                'Description'     => $details['description'] ?? '',
                'Payment Date'    => $payment['payment_date'],
                'Created By'      => $payment['created_by_name'],
                'Payment For'     => $payment['paymentable_type'] . ' #' . $payment['paymentable_id'],
            ];
        }

        $this->streamCsv('payments-export-' . date('Y-m-d-H-i-s') . '.csv', $data);
    }

    public function callback(string $gateway): void
    {
        $paymentGateway = $this->db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
            [$gateway]
        );

        if (!$paymentGateway) {
            $this->error('Unknown gateway', 400);
        }

        $raw = file_get_contents('php://input');
        $payload = array_merge($_GET, $_POST);
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = array_merge($payload, $decoded);
            }
        }

        try {
            $adapter = GatewayFactory::makeFromPaymentRecord(
                ['payment_method' => $gateway],
                $paymentGateway
            );

            $result = $adapter->processCallback($payload);

            $paymentId = (int) ($payload['payment_id'] ?? $payload['invoice_number'] ?? $result['data']['payment_id'] ?? $result['data']['invoice_id'] ?? 0);
            if ($paymentId <= 0 && isset($result['data']['payment_id'])) {
                $paymentId = (int) $result['data']['payment_id'];
            }

            $payment = $paymentId > 0
                ? $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId])
                : null;

            if ($payment) {
                $status = $result['success'] ? 'completed' : 'failed';
                $this->db->update('payments', [
                    'payment_status' => $status,
                    'transaction_id' => $result['data']['transaction_id'] ?? $payload['transaction_id'] ?? null,
                    'paid_amount'    => $result['success'] ? (float) $payment['total_amount'] : (float) $payment['paid_amount'],
                    'due_amount'     => $result['success'] ? 0 : (float) $payment['due_amount'],
                    'payment_date'   => $result['success'] ? date('Y-m-d') : $payment['payment_date'],
                    'updated_at'     => date('Y-m-d H:i:s'),
                ], 'id = ?', [$payment['id']]);
                $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$payment['id']]);
            }

            if ($result['success']) {
                $this->success([
                    'payment'    => $payment ? $this->presentPayment($payment) : null,
                    'payment_id' => $paymentId,
                    'status'     => 'completed',
                ], 'Payment completed successfully');
            }

            $this->error($result['message'] ?? 'Payment failed or was cancelled', 400, [
                'payment_id' => $paymentId,
                'status'     => 'failed',
            ]);
        } catch (\Throwable $e) {
            $this->error('Payment processing failed. Please try again later.', 400);
        }
    }

    public function webhook(string $gateway): void
    {
        $paymentGateway = $this->db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
            [$gateway]
        );

        if (!$paymentGateway) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unknown gateway']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode((string) $raw, true) ?: ($_POST ?: []);
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE']
            ?? $_SERVER['HTTP_X_SIGNATURE']
            ?? $_SERVER['HTTP_X_BKASH_SIGNATURE']
            ?? $_SERVER['HTTP_X_NAGAD_SIGNATURE']
            ?? $_SERVER['HTTP_X_ROCKET_SIGNATURE']
            ?? '';

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

            $existing = $this->db->fetch(
                "SELECT id, processed_at FROM payment_webhook_events WHERE payload_hash = ? LIMIT 1",
                [$hash]
            );

            if ($existing && $existing['processed_at']) {
                $this->json(['success' => true, 'message' => 'Duplicate webhook ignored']);
            }

            $eventId = $existing ? (int) $existing['id'] : $this->db->insert('payment_webhook_events', [
                'gateway'      => $gateway,
                'payload_hash' => $hash,
                'headers'      => json_encode($this->requestHeaders()),
                'payload'      => $payload !== [] ? json_encode($payload) : (string) $raw,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            try {
                $result = $adapter->processCallback($payload);

                $paymentId = (int) ($payload['payment_id'] ?? $result['data']['payment_id'] ?? $result['data']['invoice_id'] ?? 0);

                $payment = $paymentId > 0
                    ? $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId])
                    : null;

                if ($payment) {
                    $status = $result['success'] ? 'completed' : 'failed';
                    $this->db->update('payments', [
                        'payment_status' => $status,
                        'transaction_id' => $result['data']['transaction_id'] ?? $payload['transaction_id'] ?? null,
                        'paid_amount'    => $result['success'] ? (float) $payment['total_amount'] : (float) $payment['paid_amount'],
                        'due_amount'     => $result['success'] ? 0 : (float) $payment['due_amount'],
                        'payment_date'   => $result['success'] ? date('Y-m-d') : $payment['payment_date'],
                        'updated_at'     => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$payment['id']]);
                }

                $this->db->update('payment_webhook_events', [
                    'processed_at'  => date('Y-m-d H:i:s'),
                    'payment_id'    => $payment ? (int) $payment['id'] : null,
                    'result_status' => $result['success'] ? 'completed' : 'failed',
                    'updated_at'    => date('Y-m-d H:i:s'),
                ], 'id = ?', [$eventId]);

                $this->json([
                    'success'    => true,
                    'message'    => 'Webhook processed successfully',
                    'payment_id' => $payment ? (int) $payment['id'] : null,
                    'status'     => $result['success'] ? 'completed' : 'failed',
                ]);
            } catch (\Throwable $e) {
                if ($eventId) {
                    $this->db->update('payment_webhook_events', [
                        'processed_at'  => date('Y-m-d H:i:s'),
                        'result_status' => 'error',
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$eventId]);
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Webhook processing failed. Please try again later.']);
                exit;
            }
        } catch (\Throwable $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Webhook verification failed']);
            exit;
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
        return $_POST;
    }

    private function validateBody(array $data, array $rules): array
    {
        $validator = new Validator($data, $rules);
        if ($validator->fails()) {
            $this->error('Validation failed', 422, $validator->errors());
        }
        return $validator->validated();
    }

    private function isActive(array $gateway): bool
    {
        return (bool) ($gateway['is_active'] ?? true);
    }

    private function isOnline(array $gateway): bool
    {
        return (bool) ($gateway['is_online'] ?? false);
    }

    private function gatewayHasApi(array $gateway): bool
    {
        return (bool) ($gateway['has_api'] ?? false);
    }

    private function gatewayIsConfigured(array $gateway): bool
    {
        $code = (string) ($gateway['code'] ?? '');
        $config = config('payment.gateways.' . $code, []);
        $config = is_array($config) ? $config : [];
        $dbKey = (string) ($gateway['api_key'] ?? '');
        $dbSecret = (string) ($gateway['api_secret'] ?? '');
        $apiKey = trim((string) (($config['api_key'] ?? '') ?: $dbKey));
        $apiSecret = trim((string) (($config['api_secret'] ?? '') ?: $dbSecret));
        return $apiKey !== '' || $apiSecret !== '';
    }

    private function decodeJson(mixed $value, array $default = []): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $default;
    }

    private function presentPayment(array $payment): array
    {
        $payment['payment_details'] = $this->decodeJson($payment['payment_details'] ?? null);
        $payment['metadata'] = $this->decodeJson($payment['metadata'] ?? null);
        return $payment;
    }

    private function requestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[$key] = (string) $value;
            }
        }
        return $headers;
    }

    private function verifyWithGateway(array $payment): void
    {
        if (empty($payment['transaction_id'])) {
            return;
        }
        if (!Schema::hasTable('payment_gateways')) {
            return;
        }
        $gateway = $this->db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
            [$payment['payment_method']]
        );
        if (!$gateway || !$this->isOnline($gateway) || !$this->gatewayHasApi($gateway)) {
            return;
        }
        try {
            $adapter = GatewayFactory::makeFromPaymentRecord(
                ['payment_method' => $gateway['code']],
                $gateway
            );
            if ($adapter->verifyPayment((string) $payment['transaction_id'])) {
                $this->db->update('payments', [
                    'payment_status' => 'completed',
                    'paid_amount'    => (float) $payment['total_amount'],
                    'due_amount'     => 0,
                    'payment_date'   => $payment['payment_date'] ?? date('Y-m-d'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ], 'id = ?', [$payment['id']]);
            }
        } catch (\Throwable) {
            // Verification is best-effort; keep the stored state.
        }
    }

    private function streamCsv(string $filename, array $rows): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if (count($rows) > 0) {
            fputcsv($out, array_keys($rows[0]));
        }
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}