<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Services\ActivityLog;

/**
 * Shared behaviour for Eskoofy website payment gateways.
 *
 * Concrete gateways implement process()/verify() and provide their own
 * HTTP + signing logic. This base supplies:
 *   - config access (config/gateways.php block for this gateway code)
 *   - a dependency-free curl helper
 *   - payment row update + activity logging helpers
 */
abstract class AbstractGateway implements PaymentGatewayInterface
{
    protected DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Configuration block for this gateway (config/gateways.php).
     *
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        $file = dirname(__DIR__, 2) . '/config/gateways.php';
        $all  = file_exists($file) ? (array) require $file : ['gateways' => []];
        $code = $this->id();
        $block = $all['gateways'][$code] ?? [];
        $block = is_array($block) ? $block : [];

        // Admin-managed overrides stored in the settings table
        // (Admin → Payment gateways): gateway.<code>.<field>.
        try {
            $settings = \App\Models\Settings::all();
        } catch (\Throwable) {
            $settings = [];
        }
        $prefix = 'gateway.' . $code . '.';
        foreach ($settings as $key => $value) {
            if (!str_starts_with((string) $key, $prefix)) {
                continue;
            }
            $field = substr((string) $key, strlen($prefix));
            if ($field === '' || $field === 'enabled') {
                continue;
            }
            $block[$field] = $value;
        }
        if (isset($block['test_mode']) && is_string($block['test_mode'])) {
            $block['test_mode'] = in_array(strtolower($block['test_mode']), ['1', 'true', 'yes', 'on'], true);
        }

        return $block;
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        $cfg = $this->config();

        return $cfg[$key] ?? $default;
    }

    /**
     * Mark a payment row paid (transaction + timestamp).
     */
    protected function markPaid(array $payment, string $transaction, array $extra = []): void
    {
        $updates = [
            'transaction_id' => $transaction,
            'status'         => 'paid',
            'paid_at'        => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        foreach ($extra as $k => $v) {
            $updates[$k] = $v;
        }
        $this->db->update('payments', $updates, 'id = ?', [(int) $payment['id']]);
    }

    protected function logProcessed(array $payment, string $transaction): void
    {
        ActivityLog::log('payment.processed', 'system', null, [
            'payment_id'     => (int) $payment['id'],
            'gateway'        => $this->id(),
            'transaction_id' => $transaction,
        ]);
    }

    /**
     * Dependency-free POST helper.
     *
     * @param  array<string,string>  $headers
     * @return array{ok: bool, code: int, body: string, decoded: mixed, error?: string}
     */
    protected function http(string $url, array $headers = [], ?string $body = null, string $method = 'POST'): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);

        $raw   = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['ok' => false, 'code' => 0, 'body' => '', 'decoded' => null, 'error' => $error ?: 'curl error ' . $errno];
        }

        return [
            'ok'      => $code >= 200 && $code < 300,
            'code'    => $code,
            'body'    => (string) $raw,
            'decoded' => json_decode((string) $raw, true),
        ];
    }
}