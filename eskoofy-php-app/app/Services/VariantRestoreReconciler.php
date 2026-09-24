<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DatabaseInterface;

/**
 * Cross-variant restore reconciliation for the raw-PHP app.
 *
 * Raw SQL backups carry a `-- eskoofy-variant:` header (see BackupController).
 * When the source variant differs from the receiving variant, this service
 * re-sets variant-owned configuration rows to the receiving profile's defaults
 * (gateways, currency, payment defaults, Bengali-only content) so the restored
 * school matches the target profile. Business data is always restored verbatim.
 */
class VariantRestoreReconciler
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * @param  array<string, list<string>>  $knownColumns  per-table column list;
     *                                                     falls back to DB introspection when absent
     * @param  string|null  $targetVariant  receiving variant (defaults to the config profile)
     * @return list<string> human-readable notes for the operator
     */
    public function reconcile(?string $sourceVariant, array $knownColumns = [], ?string $targetVariant = null): array
    {
        $target = $targetVariant ?? (string) (config('eskoolfy.variant') ?? 'bd');
        if ($sourceVariant === null || $sourceVariant === $target) {
            return [];
        }

        $restore = config('eskoolfy.restore', []);
        $gateways = (array) ($restore['gateways'][$target] ?? []);
        $settings = (array) ($restore['settings'] ?? []);
        $currency = (string) ($settings['currency'][$target] ?? 'BDT');
        $notes = [];

        if ($this->db->hasTable('payment_gateways')) {
            foreach ($this->db->fetchAll('SELECT code FROM payment_gateways') as $row) {
                $code = (string) $row['code'];
                if (! in_array($code, $gateways, true)) {
                    $this->db->update('payment_gateways', ['is_active' => 0], 'code = ?', [$code]);
                    $notes[] = "Gateway '{$code}' deactivated (not part of the {$target} profile).";
                }
            }
            foreach ($gateways as $code) {
                if ($this->db->count('payment_gateways', 'code = ?', [$code]) > 0) {
                    $this->db->update('payment_gateways', ['is_active' => 1, 'currency' => $currency], 'code = ?', [$code]);
                    $notes[] = "Gateway '{$code}' activated ({$currency}).";
                } else {
                    $notes[] = "Gateway '{$code}' is missing after restore — add it in Settings › Payments.";
                }
            }
        }

        if ($this->db->hasTable('website_settings')) {
            $assignments = [];
            foreach ([
                'currency' => $currency,
                'default_payment_method' => $settings['default_payment_method'][$target] ?? null,
                'default_locale' => $settings['default_locale'][$target] ?? null,
            ] as $column => $value) {
                if ($value !== null) {
                    $assignments[$column] = $value;
                }
            }
            if ($assignments !== []) {
                $this->db->update('website_settings', $assignments, '1=1');
                $notes[] = "website_settings reconciled to the {$target} profile ({$currency}).";
            }
        }

        if ($target === 'int' && ($restore['strip_bangla_for_int'] ?? true)) {
            foreach (['website_settings', 'admission_settings', 'website_contents'] as $table) {
                if (! $this->db->hasTable($table)) {
                    continue;
                }
                foreach ($this->tableColumns($table, $knownColumns) as $column) {
                    $isBn = str_ends_with($column, '_bn');
                    $isPaymentNumber = $table === 'admission_settings' && $column === 'payment_number';
                    if (! $isBn && ! $isPaymentNumber) {
                        continue;
                    }
                    $this->db->update($table, [$column => null], '1=1');
                    $notes[] = "{$table}.{$column} cleared (Bengali content has no home in the int profile).";
                }
            }
        }

        return $notes;
    }

    /**
     * @param  array<string, list<string>>  $knownColumns
     * @return list<string>
     */
    private function tableColumns(string $table, array $knownColumns): array
    {
        if (isset($knownColumns[$table]) && $knownColumns[$table] !== []) {
            return $knownColumns[$table];
        }

        return array_map(
            static fn (array $row) => (string) $row['Field'],
            $this->db->fetchAll("SHOW COLUMNS FROM `{$table}`")
        );
    }
}