<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;
use App\Services\LicenseManager;

class LicenseApiController extends Controller
{
    private LicenseManager $manager;

    public function __construct()
    {
        $this->manager = new LicenseManager();
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input');

        return json_decode($raw !== false ? $raw : '', true) ?? $_POST;
    }

    public function activate(): void
    {
        $data = $this->body();
        $key = strtoupper(trim((string) ($data['license_key'] ?? '')));
        $domain = strtolower(trim((string) ($data['domain'] ?? $_SERVER['HTTP_HOST'] ?? '')));
        $machine = $data['machine_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null);

        if ($key === '' || $domain === '') {
            $this->error('license_key and domain are required.', 422);
        }

        $result = $this->manager->activate($key, $domain, is_string($machine) ? $machine : null, is_string($ip) ? $ip : null);

        if (($result['status'] ?? '') !== 'ok') {
            $this->error($result['message'] ?? 'Activation failed.', 422, ['code' => $result['code'] ?? null]);
        }

        $this->success($result['data'], $result['message'] ?: 'License activated.');
    }

    public function checkLicense(): void
    {
        $data = $this->body();
        $key = strtoupper(trim((string) ($data['license_key'] ?? '')));
        $domain = strtolower(trim((string) ($data['domain'] ?? $_SERVER['HTTP_HOST'] ?? '')));
        $machine = $data['machine_id'] ?? null;

        if ($key === '' || $domain === '') {
            $this->error('license_key and domain are required.', 422);
        }

        $result = $this->manager->validate($key, $domain, is_string($machine) ? $machine : null);

        if (($result['status'] ?? '') !== 'ok') {
            $this->error($result['message'] ?? 'License invalid.', 422, ['code' => $result['code'] ?? null]);
        }

        $this->success($result['data'], $result['message'] ?: 'License valid.');
    }

    public function deactivate(): void
    {
        $data = $this->body();
        $key = strtoupper(trim((string) ($data['license_key'] ?? '')));
        $domain = strtolower(trim((string) ($data['domain'] ?? $_SERVER['HTTP_HOST'] ?? '')));
        $machine = $data['machine_id'] ?? null;

        if ($key === '' || $domain === '') {
            $this->error('license_key and domain are required.', 422);
        }

        $result = $this->manager->deactivate($key, $domain, is_string($machine) ? $machine : null);

        if (($result['status'] ?? '') !== 'ok') {
            $this->error($result['message'] ?? 'Deactivation failed.', 422, ['code' => $result['code'] ?? null]);
        }

        $this->success($result['data'], $result['message'] ?: 'License deactivated.');
    }

    public function status(): void
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $db = Database::getInstance();

        $customer = $db->fetch("SELECT id, name, email FROM customers WHERE api_token = ? AND deleted_at IS NULL LIMIT 1", [$apiKey]);
        if (!$customer) {
            $this->error('Invalid API key.', 401);
        }

        $licenses = $db->fetchAll(
            "SELECT license_key, product, status,
                (SELECT COUNT(*) FROM license_activations WHERE license_id = l.id AND deactivated_at IS NULL) AS active_activations,
                max_activations, starts_at, expires_at, created_at
             FROM licenses l
             WHERE l.customer_id = ? AND l.deleted_at IS NULL
             ORDER BY l.id DESC",
            [(int) $customer['id']]
        );

        $this->success([
            'customer' => $customer,
            'licenses' => $licenses,
        ], 'License status retrieved.');
    }

    public function ping(): void
    {
        $this->success([
            'service'   => 'eskoofy-license-server',
            'version'   => '1.0.0',
            'timestamp' => gmdate('c'),
        ], 'License server is online.');
    }
}
