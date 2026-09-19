<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Validator;

class PaymentGatewayController extends Controller
{
    private const SENSITIVE = ['api_key', 'api_secret', 'api_username', 'api_password'];

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

        $where = 'deleted_at IS NULL';
        $params = [];

        if (($_GET['active_only'] ?? '') !== '' && (bool) $_GET['active_only']) {
            $where .= ' AND is_active = 1';
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM payment_gateways WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT * FROM payment_gateways
             WHERE {$where}
             ORDER BY sort_order ASC, name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->paginated([
            'data'         => array_map(fn ($row) => $this->presentGateway($row), $rows),
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function store(): void
    {
        $data = $this->validateBody($this->body(), [
            'name'              => 'required|max:255',
            'code'              => 'required|max:50',
            'type'              => 'required|in:bank,mobile_financial_service,online_payment,other',
            'is_active'         => 'numeric',
            'is_online'         => 'numeric',
            'has_api'           => 'numeric',
            'test_mode'         => 'numeric',
            'currency'          => 'max:3',
            'fee_percentage'    => 'numeric',
            'fee_fixed'         => 'numeric',
            'min_amount'        => 'numeric',
            'max_amount'        => 'numeric',
            'description'       => '',
            'instructions'      => '',
            'sort_order'        => 'numeric',
        ]);

        $existing = $this->db->fetch(
            "SELECT id FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
            [$data['code']]
        );
        if ($existing) {
            $this->error('The code has already been taken.', 422);
        }

        $id = $this->db->insert('payment_gateways', [
            'name'              => $data['name'],
            'code'              => $data['code'],
            'type'              => $data['type'],
            'is_active'         => $data['is_active'] ?? 1,
            'is_online'         => $data['is_online'] ?? 0,
            'has_api'           => $data['has_api'] ?? 0,
            'test_mode'         => $data['test_mode'] ?? 1,
            'currency'          => $data['currency'] ?? 'BDT',
            'fee_percentage'    => $data['fee_percentage'] ?? 0,
            'fee_fixed'         => $data['fee_fixed'] ?? 0,
            'min_amount'        => $data['min_amount'] ?? null,
            'max_amount'        => $data['max_amount'] ?? null,
            'description'       => $data['description'] ?? null,
            'instructions'      => $data['instructions'] ?? null,
            'sort_order'        => $data['sort_order'] ?? 0,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $gateway = $this->db->fetch("SELECT * FROM payment_gateways WHERE id = ? LIMIT 1", [$id]);

        $this->success($this->presentGateway($gateway), 'Payment gateway created', 201);
    }

    public function show(int $id): void
    {
        $gateway = $this->db->fetch("SELECT * FROM payment_gateways WHERE id = ? LIMIT 1", [$id]);
        if (!$gateway) {
            $this->error('Payment gateway not found', 404);
        }

        $this->success($this->presentGateway($gateway), 'Payment gateway retrieved');
    }

    public function update(int $id): void
    {
        $gateway = $this->db->fetch("SELECT * FROM payment_gateways WHERE id = ? LIMIT 1", [$id]);
        if (!$gateway) {
            $this->error('Payment gateway not found', 404);
        }

        $data = $this->validateBody($this->body(), [
            'name'              => 'max:255',
            'code'              => 'max:50',
            'type'              => 'in:bank,mobile_financial_service,online_payment,other',
            'is_active'         => 'numeric',
            'is_online'         => 'numeric',
            'has_api'           => 'numeric',
            'test_mode'         => 'numeric',
            'currency'          => 'max:3',
            'fee_percentage'    => 'numeric',
            'fee_fixed'         => 'numeric',
            'min_amount'        => 'numeric',
            'max_amount'        => 'numeric',
            'description'       => '',
            'instructions'      => '',
            'sort_order'        => 'numeric',
        ]);

        if (isset($data['code']) && $data['code'] !== $gateway['code']) {
            $existing = $this->db->fetch(
                "SELECT id FROM payment_gateways WHERE code = ? AND id <> ? AND deleted_at IS NULL LIMIT 1",
                [$data['code'], $id]
            );
            if ($existing) {
                $this->error('The code has already been taken.', 422);
            }
        }

        $fields = [
            'name', 'code', 'type', 'is_active', 'is_online', 'has_api',
            'test_mode', 'currency', 'fee_percentage', 'fee_fixed',
            'min_amount', 'max_amount', 'description', 'instructions', 'sort_order',
        ];
        $updates = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }
        if ($updates === []) {
            $this->success($this->presentGateway($gateway), 'Payment gateway updated');
        }
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('payment_gateways', $updates, 'id = ?', [$id]);

        $gateway = $this->db->fetch("SELECT * FROM payment_gateways WHERE id = ? LIMIT 1", [$id]);

        $this->success($this->presentGateway($gateway), 'Payment gateway updated');
    }

    public function destroy(int $id): void
    {
        $gateway = $this->db->fetch("SELECT * FROM payment_gateways WHERE id = ? LIMIT 1", [$id]);
        if (!$gateway) {
            $this->error('Payment gateway not found', 404);
        }

        $this->db->update('payment_gateways', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success(null, 'Payment gateway deleted');
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

    private function presentGateway(array $gateway): array
    {
        foreach (self::SENSITIVE as $key) {
            if (array_key_exists($key, $gateway)) {
                $gateway[$key] = $gateway[$key] !== null && $gateway[$key] !== '' ? '[redacted]' : null;
            }
        }
        if (isset($gateway['supported_currencies']) && is_string($gateway['supported_currencies'])) {
            $gateway['supported_currencies'] = json_decode($gateway['supported_currencies'], true) ?: [];
        }
        if (isset($gateway['extra_attributes']) && is_string($gateway['extra_attributes'])) {
            $gateway['extra_attributes'] = json_decode($gateway['extra_attributes'], true) ?: [];
        }
        return $gateway;
    }
}