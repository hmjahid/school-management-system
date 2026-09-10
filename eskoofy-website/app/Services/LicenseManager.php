<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\DatabaseInterface;

class LicenseManager
{
    private ?DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db;
    }

    private function db(): DatabaseInterface
    {
        return $this->db ??= Database::getInstance();
    }

    /**
     * Generate a strong license key: PREFIX-XXXX-XXXX-XXXX (chunks of 4).
     */
    public function generateKey(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $alphabetLength = strlen($alphabet);
        $chunks = (int) ($_ENV['LICENSE_KEY_CHUNKS'] ?? 4);
        $length = (int) ($_ENV['LICENSE_KEY_LENGTH'] ?? 4);
        $prefix = $_ENV['LICENSE_KEY_PREFIX'] ?? 'ESK';

        $parts = [];
        for ($i = 0; $i < $chunks; $i++) {
            $chunk = '';
            for ($j = 0; $j < $length; $j++) {
                $chunk .= $alphabet[random_int(0, $alphabetLength - 1)];
            }
            $parts[] = $chunk;
        }

        return $prefix . '-' . implode('-', $parts);
    }

    /**
     * Issue a license key for a customer/plan. Optionally records the source payment.
     *
     * @return array{license: array<string, mixed>, payment_id: ?int}
     */
    public function issue(int $customerId, int $planId, string $product, array $opts = []): array
    {
        $plan = $this->db()->fetch("SELECT * FROM plans WHERE id = ? AND active = 1", [$planId]) ?: $this->db()->fetch("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            throw new \InvalidArgumentException('Plan not found.');
        }

        $startsAt = $opts['starts_at'] ?? date('Y-m-d H:i:s');
        $expiresAt = $opts['expires_at'] ?? $this->expiryFor($plan, $startsAt);
        $maxActivations = (int) ($plan['max_activations'] ?? 3);
        $key = $opts['license_key'] ?? $this->generateKey();

        $licenseId = $this->db()->insert('licenses', [
            'license_key'      => $key,
            'customer_id'      => $customerId,
            'plan_id'          => $planId,
            'product'          => $product,
            'status'           => 'active',
            'max_activations'  => $maxActivations,
            'starts_at'        => $startsAt,
            'expires_at'       => $expiresAt,
            'metadata'         => json_encode($opts['metadata'] ?? [], JSON_UNESCAPED_SLASHES),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $paymentId = null;
        if (!empty($opts['payment'])) {
            $paymentId = (int) $opts['payment'];
            $this->db()->update('payments', [
                'license_id' => $licenseId,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$paymentId]);
        }

        ActivityLog::log('license.issued', 'system', $customerId, [
            'license_key' => $key,
            'license_id'  => $licenseId,
            'plan_id'     => $planId,
            'product'     => $product,
        ]);

        return ['license' => $this->byId($licenseId), 'payment_id' => $paymentId];
    }

    /**
     * Find a license by its key.
     *
     * @return array<string, mixed>|null
     */
    public function byKey(string $key): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM licenses WHERE license_key = ? AND deleted_at IS NULL LIMIT 1",
            [strtoupper(trim($key))]
        );
    }

    /**
     * Find a license by id.
     *
     * @return array<string, mixed>|null
     */
    public function byId(int $id): ?array
    {
        return $this->db()->fetch("SELECT * FROM licenses WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    /**
     * Whether a license is past its expiry date.
     */
    public function isExpired(array $license): bool
    {
        $expiresAt = $license['expires_at'] ?? null;
        if (!$expiresAt) {
            return false;
        }

        return strtotime($expiresAt) < time() && $expiresAt !== null && $expiresAt !== '';
    }

    /**
     * Compute expiry for a plan from a start date.
     */
    public function expiryFor(array $plan, string $startsAt): ?string
    {
        $period = $plan['period'] ?? 'one-time';
        if (($period === 'one-time' || $period === 'lifetime' || $period === '') || $period === null) {
            return null;
        }

        $ts = strtotime($startsAt);
        if ($ts === false) {
            $ts = time();
        }

        return match ($period) {
            'monthly' => date('Y-m-d H:i:s', strtotime('+1 month', $ts)),
            'yearly', 'annual' => date('Y-m-d H:i:s', strtotime('+1 year', $ts)),
            default => null,
        };
    }

    /**
     * Active (not deactivated) activation rows for a license.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activations(int $licenseId): array
    {
        return $this->db()->fetchAll(
            "SELECT * FROM license_activations WHERE license_id = ? ORDER BY id ASC",
            [$licenseId]
        );
    }

    /**
     * Count currently-active activations for a license.
     */
    public function activeActivationCount(int $licenseId): int
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS c FROM license_activations WHERE license_id = ? AND deactivated_at IS NULL",
            [$licenseId]
        );

        return (int) ($row['c'] ?? 0);
    }

    /**
     * Activate a license for a domain/machine. Enforces max_activations.
     *
     * @return array<string, mixed>
     */
    public function activate(string $licenseKey, string $domain, ?string $machineId = null, ?string $ip = null): array
    {
        $license = $this->byKey($licenseKey);
        if (!$license) {
            return $this->fail('Invalid license key.', 'invalid_license');
        }
        if (($license['status'] ?? '') !== 'active') {
            return $this->fail('License is ' . $license['status'] . '.', 'license_' . $license['status']);
        }
        if ($this->isExpired($license)) {
            return $this->fail('License has expired.', 'license_expired');
        }

        $licenseId = (int) $license['id'];
        $domain = strtolower(trim($domain));
        $machine = $machineId ? substr(trim($machineId), 0, 191) : null;

        $existing = $this->db()->fetch(
            "SELECT * FROM license_activations WHERE license_id = ? AND domain = ? AND (machine_id <=> ? OR (? IS NULL AND machine_id IS NULL))",
            [$licenseId, $domain, $machine, $machine]
        );

        if ($existing) {
            if (($existing['deactivated_at'] ?? null) === null) {
                return [
                    'status'  => 'ok',
                    'message' => 'Already activated on this domain/machine.',
                    'data'    => ['activation_id' => (int) $existing['id']],
                ];
            }

            $this->db()->update('license_activations', [
                'deactivated_at' => null,
                'ip_address'     => $ip,
                'activated_at'   => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $existing['id']]);

            ActivityLog::log('license.reactivated', 'system', (int) $license['customer_id'], [
                'license_key' => $licenseKey,
                'domain'      => $domain,
            ]);

            return [
                'status'  => 'ok',
                'message' => 'License activation restored.',
                'data'    => ['activation_id' => (int) $existing['id']],
            ];
        }

        $active = $this->activeActivationCount($licenseId);
        $max = (int) $license['max_activations'];
        if ($active >= $max) {
            return $this->fail(
                'Maximum number of activations (' . $max . ') reached for this license.',
                'max_activations_reached'
            );
        }

        $activationId = $this->db()->insert('license_activations', [
            'license_id'    => $licenseId,
            'domain'        => $domain,
            'machine_id'    => $machine,
            'ip_address'    => $ip,
            'activated_at'  => date('Y-m-d H:i:s'),
            'deactivated_at' => null,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::log('license.activated', 'system', (int) $license['customer_id'], [
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ]);

        return [
            'status'  => 'ok',
            'message' => 'License activated.',
            'data'    => ['activation_id' => $activationId],
        ];
    }

    /**
     * Deactivate a license for a domain/machine.
     *
     * @return array<string, mixed>
     */
    public function deactivate(string $licenseKey, string $domain, ?string $machineId = null): array
    {
        $license = $this->byKey($licenseKey);
        if (!$license) {
            return $this->fail('Invalid license key.', 'invalid_license');
        }

        $licenseId = (int) $license['id'];
        $domain = strtolower(trim($domain));
        $machine = $machineId ? substr(trim($machineId), 0, 191) : null;

        $existing = $this->db()->fetch(
            "SELECT * FROM license_activations WHERE license_id = ? AND domain = ? AND (machine_id <=> ? OR (? IS NULL AND machine_id IS NULL))",
            [$licenseId, $domain, $machine, $machine]
        );

        if (!$existing) {
            return $this->fail('No activation found for this domain/machine.', 'no_activation');
        }

        $this->db()->update('license_activations', [
            'deactivated_at' => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $existing['id']]);

        ActivityLog::log('license.deactivated', 'system', (int) $license['customer_id'], [
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ]);

        return [
            'status'  => 'ok',
            'message' => 'License deactivated.',
            'data'    => ['activation_id' => (int) $existing['id']],
        ];
    }

    /**
     * Validate a license for a domain/machine.
     *
     * @return array<string, mixed>
     */
    public function validate(string $licenseKey, string $domain, ?string $machineId = null): array
    {
        $license = $this->byKey($licenseKey);
        if (!$license) {
            return $this->fail('Invalid license key.', 'invalid_license');
        }
        if (($license['status'] ?? '') !== 'active') {
            return $this->fail('License is ' . $license['status'] . '.', 'license_' . $license['status']);
        }
        if ($this->isExpired($license)) {
            return $this->fail('License has expired.', 'license_expired');
        }

        $licenseId = (int) $license['id'];
        $domain = strtolower(trim($domain));
        $machine = $machineId ? substr(trim($machineId), 0, 191) : null;

        $activation = $this->db()->fetch(
            "SELECT * FROM license_activations WHERE license_id = ? AND domain = ? AND (machine_id <=> ? OR (? IS NULL AND machine_id IS NULL)) AND deactivated_at IS NULL",
            [$licenseId, $domain, $machine, $machine]
        );

        $plan = $this->db()->fetch("SELECT name, product FROM plans WHERE id = ?", [(int) $license['plan_id']]);

        return [
            'status'  => 'ok',
            'message' => 'License valid.',
            'data'    => [
                'license_key' => $license['license_key'],
                'product'     => $plan['product'] ?? $license['product'],
                'plan'        => $plan['name'] ?? null,
                'expires_at'  => $license['expires_at'],
                'max_activations' => (int) $license['max_activations'],
                'active_activations' => $this->activeActivationCount($licenseId),
                'activated_on_this'  => (bool) $activation,
            ],
        ];
    }

    /**
     * Renew (extend) a license. When gateway is manual it is completed immediately.
     *
     * @return array<string, mixed>
     */
    public function renew(int $licenseId, ?int $planId = null, string $gateway = 'manual', array $paymentOpts = []): array
    {
        $license = $this->byId($licenseId);
        if (!$license) {
            return $this->fail('License not found.', 'license_not_found');
        }

        $plan = $this->db()->fetch("SELECT * FROM plans WHERE id = ?", [
            $planId ?? (int) $license['plan_id'],
        ]);
        if (!$plan) {
            return $this->fail('Renewal plan not found.', 'plan_not_found');
        }

        $base = $license['expires_at'];
        if (!$base || strtotime($base) < time()) {
            $base = date('Y-m-d H:i:s');
        }

        $newExpiry = $this->expiryFor($plan, $base) ?? $license['expires_at'];

        $paymentId = $this->db()->insert('payments', [
            'customer_id' => (int) $license['customer_id'],
            'license_id'  => $licenseId,
            'plan_id'     => (int) $plan['id'],
            'gateway'     => $gateway,
            'reference'   => $paymentOpts['reference'] ?? ('REN-' . strtoupper(bin2hex(random_bytes(4)))),
            'amount'      => $plan['price'],
            'currency'    => $plan['currency'] ?? 'USD',
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $gatewayService = \App\Gateways\GatewayFactory::make($gateway);
        $payment = $this->db()->fetch("SELECT * FROM payments WHERE id = ?", [$paymentId]);
        $result = $gatewayService->process($license, $payment);

        $this->db()->update('licenses', [
            'expires_at' => $newExpiry,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$licenseId]);

        $this->db()->insert('subscriptions', [
            'customer_id'          => (int) $license['customer_id'],
            'license_id'           => $licenseId,
            'plan_id'              => (int) $plan['id'],
            'status'               => 'active',
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end'   => $newExpiry,
            'renews_at'            => $newExpiry,
            'gateway'              => $gateway,
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::log('license.renewed', 'system', (int) $license['customer_id'], [
            'license_id' => $licenseId,
            'plan_id'    => (int) $plan['id'],
            'expires_at' => $newExpiry,
        ]);

        return [
            'status'       => 'ok',
            'message'      => 'License renewed until ' . ($newExpiry ?? 'forever') . '.',
            'license'      => $this->byId($licenseId),
            'payment_id'   => $paymentId,
            'gateway'      => $result,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fail(string $message, string $code): array
    {
        return ['status' => 'error', 'message' => $message, 'code' => $code];
    }
}