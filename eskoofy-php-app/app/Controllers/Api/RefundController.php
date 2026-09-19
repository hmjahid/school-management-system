<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Schema;
use App\Core\Validator;
use App\Gateways\GatewayFactory;

class RefundController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 15)));
        $offset = ($page - 1) * $perPage;

        $where = 'r.deleted_at IS NULL';
        $params = [];

        if (($_GET['status'] ?? '') !== '') {
            $where .= ' AND r.status = ?';
            $params[] = $_GET['status'];
        }
        if (($_GET['payment_id'] ?? '') !== '') {
            $where .= ' AND r.payment_id = ?';
            $params[] = (int) $_GET['payment_id'];
        }
        if (($_GET['user_id'] ?? '') !== '') {
            $where .= ' AND r.user_id = ?';
            $params[] = (int) $_GET['user_id'];
        }
        if (($_GET['date_from'] ?? '') !== '' && ($_GET['date_to'] ?? '') !== '') {
            $where .= ' AND r.created_at BETWEEN ? AND ?';
            $params[] = $_GET['date_from'] . ' 00:00:00';
            $params[] = $_GET['date_to'] . ' 23:59:59';
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM refunds r WHERE {$where}", $params
        )['cnt'] ?? 0);

        $sortBy = in_array($_GET['sort_by'] ?? '', ['created_at', 'amount', 'status'], true)
            ? $_GET['sort_by'] : 'created_at';
        $sortOrder = strtolower($_GET['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $rows = $this->db->fetchAll(
            "SELECT r.*, u.name as requester_name, p.invoice_number, p.payment_method, p.payment_status
             FROM refunds r
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN payments p ON r.payment_id = p.id
             WHERE {$where}
             ORDER BY r.{$sortBy} {$sortOrder}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->paginated([
            'data'         => $rows,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function show(int $id): void
    {
        $refund = $this->db->fetch(
            "SELECT r.*, u.name as requester_name, u2.name as processed_by_name,
                    p.invoice_number, p.payment_method, p.payment_status
             FROM refunds r
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN users u2 ON r.processed_by = u2.id
             LEFT JOIN payments p ON r.payment_id = p.id
             WHERE r.id = ? LIMIT 1",
            [$id]
        );

        if (!$refund) {
            $this->error('Refund not found', 404);
        }

        $this->success($refund, 'Refund retrieved');
    }

    public function store(int $paymentId): void
    {
        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);
        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        $data = $this->validateBody($this->body(), [
            'amount'  => 'required|numeric',
            'reason'  => 'required|max:255',
            'metadata'=> '',
        ]);

        $amount = (float) $data['amount'];
        $refundable = $this->refundableAmount($payment);

        if ($amount <= 0 || $amount > $refundable) {
            $this->error("The requested refund amount exceeds the refundable amount (max {$refundable}).", 422);
        }

        $duplicate = $this->db->fetch(
            "SELECT id FROM refunds
             WHERE payment_id = ? AND amount = ? AND status IN ('pending','processing','completed') AND deleted_at IS NULL
             LIMIT 1",
            [$paymentId, $amount]
        );
        if ($duplicate) {
            $this->error('A refund of this amount has already been processed for this payment.', 422);
        }

        $userId = \App\Core\Auth::id();

        $refundId = $this->db->insert('refunds', [
            'payment_id'  => $paymentId,
            'user_id'     => $userId,
            'amount'      => $amount,
            'currency'    => 'BDT',
            'status'      => 'pending',
            'reason'      => $data['reason'],
            'metadata'    => isset($data['metadata']) && is_array($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $refund = $this->db->fetch("SELECT * FROM refunds WHERE id = ? LIMIT 1", [$refundId]);

        $this->success($refund, 'Refund initiated successfully', 201);
    }

    public function process(int $id): void
    {
        $refund = $this->db->fetch("SELECT * FROM refunds WHERE id = ? LIMIT 1", [$id]);
        if (!$refund) {
            $this->error('Refund not found', 404);
        }

        if ($refund['status'] !== 'pending') {
            $this->error('Only pending refunds can be processed', 400);
        }

        $data = $this->body();
        $transactionId = $data['transaction_id'] ?? null;

        $this->db->update('refunds', [
            'status'         => 'processing',
            'processed_by'   => \App\Core\Auth::id(),
            'transaction_id' => $transactionId,
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$refund['payment_id']]);

        $status = 'completed';
        if ($payment && !empty($payment['transaction_id']) && Schema::hasTable('payment_gateways')) {
            $gateway = $this->db->fetch(
                "SELECT * FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
                [$payment['payment_method']]
            );
            if ($gateway && (bool) ($gateway['is_online'] ?? false) && (bool) ($gateway['has_api'] ?? false)) {
                try {
                    $adapter = GatewayFactory::makeFromPaymentRecord(
                        ['payment_method' => $gateway['code']],
                        $gateway
                    );
                    $result = $adapter->refund(
                        (string) $payment['transaction_id'],
                        (float) $refund['amount'],
                        (string) ($refund['reason'] ?? 'Refund')
                    );
                    if (!$result['success']) {
                        $status = 'failed';
                    } else {
                        $transactionId = $result['data']['refund_id'] ?? $transactionId;
                    }
                } catch (\Throwable) {
                    $status = 'failed';
                }
            }
        }

        $this->db->update('refunds', [
            'status'         => $status,
            'transaction_id' => $transactionId,
            'processed_at'   => date('Y-m-d H:i:s'),
            'processed_by'   => \App\Core\Auth::id(),
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        if ($status === 'completed' && $payment) {
            $this->db->update('payments', [
                'payment_status' => 'refunded',
                'refund_status'  => 'refunded',
                'updated_at'     => date('Y-m-d H:i:s'),
            ], 'id = ?', [$payment['id']]);
        }

        $refund = $this->db->fetch("SELECT * FROM refunds WHERE id = ? LIMIT 1", [$id]);

        $this->success($refund, $status === 'completed' ? 'Refund processed successfully' : 'Refund processing failed');
    }

    public function cancel(int $id): void
    {
        $refund = $this->db->fetch("SELECT * FROM refunds WHERE id = ? LIMIT 1", [$id]);
        if (!$refund) {
            $this->error('Refund not found', 404);
        }

        if ($refund['status'] !== 'pending') {
            $this->error('Only pending refunds can be cancelled', 400);
        }

        $data = $this->validateBody($this->body(), [
            'reason' => 'required|max:255',
        ]);

        $this->db->update('refunds', [
            'status'     => 'cancelled',
            'reason'     => $data['reason'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $refund = $this->db->fetch("SELECT * FROM refunds WHERE id = ? LIMIT 1", [$id]);

        $this->success($refund, 'Refund cancelled');
    }

    public function statistics(): void
    {
        $rows = $this->db->fetchAll(
            "SELECT r.id, r.amount, r.status, r.created_at, p.payment_method
             FROM refunds r
             LEFT JOIN payments p ON r.payment_id = p.id
             WHERE r.deleted_at IS NULL"
        );

        $totalRefunded = 0.0;
        $counts = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'cancelled' => 0];
        $byStatus = [];
        $byMonth = [];
        $byMethod = [];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'pending');
            $amount = (float) $row['amount'];

            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            if ($status === 'completed') {
                $totalRefunded += $amount;
            }

            if (!isset($byStatus[$status])) {
                $byStatus[$status] = ['count' => 0, 'total_amount' => 0.0];
            }
            $byStatus[$status]['count']++;
            $byStatus[$status]['total_amount'] += $amount;

            $month = substr((string) $row['created_at'], 0, 7);
            if ($month !== '') {
                if (!isset($byMonth[$month])) {
                    $byMonth[$month] = ['count' => 0, 'total_amount' => 0.0];
                }
                $byMonth[$month]['count']++;
                $byMonth[$month]['total_amount'] += $amount;
            }

            $method = (string) ($row['payment_method'] ?? 'unknown');
            if (!isset($byMethod[$method])) {
                $byMethod[$method] = ['count' => 0, 'total_amount' => 0.0];
            }
            $byMethod[$method]['count']++;
            $byMethod[$method]['total_amount'] += $amount;
        }

        ksort($byMonth);

        $this->success([
            'total_refunded'             => round($totalRefunded, 2),
            'pending_count'              => $counts['pending'],
            'processing_count'           => $counts['processing'],
            'completed_count'            => $counts['completed'],
            'failed_count'               => $counts['failed'],
            'cancelled_count'            => $counts['cancelled'],
            'refunds_by_month'           => $byMonth,
            'refunds_by_status'          => $byStatus,
            'refunds_by_payment_method'  => $byMethod,
        ], 'Refund statistics retrieved');
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

    private function refundableAmount(array $payment): float
    {
        $paid = (float) ($payment['paid_amount'] ?? 0);

        $active = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM refunds
             WHERE payment_id = ? AND status IN ('pending','processing','completed') AND deleted_at IS NULL",
            [$payment['id']]
        );

        $already = (float) ($active['total'] ?? 0);

        return round(max(0.0, $paid - $already), 2);
    }
}