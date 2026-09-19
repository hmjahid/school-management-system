<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Gateways\GatewayFactory;

/**
 * Recurring payment processing — due-profile detection + payment creation.
 * Parity with eskoofy-laravel-app RecurringPaymentService (processDuePayments).
 */
class RecurringPaymentService
{
    private DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Process all due recurring payment profiles.
     *
     * @return array{processed: int, succeeded: int, failed: int, skipped: int, errors: array<int,string>}
     */
    public function processDuePayments(bool $force = false): array
    {
        $results = ['processed' => 0, 'succeeded' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];

        try {
            if (! $this->db->hasTable('recurring_payment_profiles')) {
                return $results;
            }
        } catch (\Throwable) {
            return $results;
        }

        $profiles = $this->db->fetchAll(
            "SELECT * FROM recurring_payment_profiles
             WHERE status = 'active' AND deleted_at IS NULL AND next_billing_date <= NOW()"
        );

        foreach ($profiles as $profile) {
            $result = $this->processPayment($profile);
            ++$results['processed'];
            if ($result['success']) {
                ++$results['succeeded'];
            } else {
                ++$results['failed'];
                $results['errors'][(int) $profile['id']] = $result['error'] ?? 'Unknown error';
            }
        }

        return $results;
    }

    /**
     * Process a single recurring payment profile.
     *
     * @param  array<string,mixed>  $profile
     * @return array{success: bool, error?: string, message?: string}
     */
    public function processPayment(array $profile): array
    {
        try {
            $gateway = $this->db->fetch(
                "SELECT * FROM payment_gateways WHERE code = ? AND deleted_at IS NULL LIMIT 1",
                [$profile['gateway']]
            );
            if (! $gateway) {
                return ['success' => false, 'error' => 'Gateway not configured'];
            }

            $invoice = 'RC-' . date('YmdHis') . '-' . str_pad((string) $profile['id'], 6, '0', STR_PAD_LEFT);
            $this->db->insert('payments', [
                'paymentable_type' => $profile['paymentable_type'],
                'paymentable_id'   => (int) $profile['paymentable_id'],
                'invoice_number'   => $invoice,
                'amount'           => (float) $profile['amount'],
                'paid_amount'      => 0,
                'due_amount'       => (float) $profile['amount'],
                'total_amount'     => (float) $profile['amount'],
                'payment_method'   => $profile['gateway'],
                'payment_status'   => 'pending',
                'payment_details'  => json_encode(['description' => 'Recurring payment', 'profile_id' => $profile['profile_id']]),
                'metadata'         => json_encode(['recurring_profile_id' => $profile['id']]),
                'created_by'       => (int) $profile['user_id'],
                'updated_by'       => (int) $profile['user_id'],
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);

            // Advance the billing cycle.
            $next = $this->advanceBillingDate(
                $profile['next_billing_date'],
                $profile['billing_period'],
                (int) $profile['billing_frequency']
            );
            $this->db->update('recurring_payment_profiles', [
                'next_billing_date' => $next,
                'updated_at'        => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $profile['id']]);

            return ['success' => true, 'message' => "Scheduled recurring payment for profile {$profile['profile_id']}"];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function advanceBillingDate(string $from, string $period, int $frequency): string
    {
        $ts = strtotime($from);
        return date('Y-m-d H:i:s', strtotime("+{$frequency} {$period}s", $ts));
    }
}