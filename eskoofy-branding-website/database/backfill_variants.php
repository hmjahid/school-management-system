<?php
declare(strict_types=1);

/**
 * Populate `customers.variant` and `licenses.variant` for installs upgraded
 * from before the columns existed.
 *
 * Idempotent: it never overwrites a value that is already a valid variant, so
 * running it twice is a no-op. Safe to re-run after new signups.
 *
 * Usage:
 *   php database/backfill_variants.php --dry-run   # report only, changes nothing
 *   php database/backfill_variants.php --apply     # write
 *
 * @see database/migrations/2026_10_04_add_variants.sql
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script may only be run from the command line.\n");
}

$root = dirname(__DIR__);
require_once $root . '/app/Core/bootstrap.php';

use App\Core\Database;
use App\Gateways\GatewayFactory;

$apply = in_array('--apply', $argv, true);
$dryRun = ! $apply;

if (! in_array('--apply', $argv, true) && ! in_array('--dry-run', $argv, true)) {
    exit("Specify --dry-run (report only) or --apply (write).\n"
        . "Usage: php database/backfill_variants.php --dry-run|--apply\n");
}

// A migration script is run by hand over SSH; a raw PDO stack trace tells the
// operator nothing about which host/credentials failed.
try {
    $db = Database::getInstance();
} catch (\PDOException $e) {
    fwrite(STDERR, "Could not connect to the database.\n\n"
        . '  ' . $e->getMessage() . "\n\n"
        . "Check the DB_* values in .env and that MySQL is running.\n");
    exit(1);
}

$chunk = 1000;

echo "Eskoofy variant backfill — " . ($apply ? 'APPLY' : 'DRY RUN') . "\n";
echo str_repeat('-', 60) . "\n";

/**
 * @return list<array<string, mixed>>
 */
$readCustomers = static function (int $offset, int $limit) use ($db): array {
    return $db->fetchAll(
        'SELECT c.id, c.country, c.variant,
                (SELECT p.variant FROM payments p
                  WHERE p.customer_id = c.id AND p.variant IS NOT NULL
                  ORDER BY p.id DESC LIMIT 1) AS payment_variant
           FROM customers c
          WHERE c.deleted_at IS NULL
          ORDER BY c.id
          LIMIT ' . $limit . ' OFFSET ' . $offset
    );
};

/**
 * @return list<array<string, mixed>>
 */
$readLicenses = static function (int $offset, int $limit) use ($db): array {
    return $db->fetchAll(
        'SELECT l.id, l.variant, c.variant AS customer_variant
           FROM licenses l
           LEFT JOIN customers c ON c.id = l.customer_id
          WHERE l.deleted_at IS NULL
          ORDER BY l.id
          LIMIT ' . $limit . ' OFFSET ' . $offset
    );
};

$isValid = static fn ($v): bool => is_string($v) && in_array(strtolower(trim($v)), ['bd', 'int'], true);
$isBd = static fn (?string $country): bool => GatewayFactory::isBdCountry($country);

$stats = [
    'customers_scanned' => 0, 'customers_updated' => 0, 'customers_skipped' => 0,
    'licenses_scanned'  => 0, 'licenses_updated'  => 0, 'licenses_skipped'  => 0,
];
$ambiguous = [];

// Projected end-state, tracked in memory so `--dry-run` reports what *would*
// happen instead of the unchanged current distribution. Seeded with the rows
// that are already resolved; only unresolved rows are ever updated, so there is
// no double counting.
$projected = ['customers' => ['bd' => 0, 'int' => 0], 'licenses' => ['bd' => 0, 'int' => 0]];
foreach (['bd', 'int'] as $v) {
    $projected['customers'][$v] = $db->count('customers', 'variant = ? AND deleted_at IS NULL', [$v]);
    $projected['licenses'][$v]  = $db->count('licenses', 'variant = ? AND deleted_at IS NULL', [$v]);
}

// ------------------------------------------------------------- customers ----
$offset = 0;
do {
    $rows = $readCustomers($offset, $chunk);
    if ($rows === []) {
        break;
    }

    foreach ($rows as $row) {
        $stats['customers_scanned']++;
        $current = $row['variant'] ?? null;

        if ($isValid($current)) {
            $stats['customers_skipped']++;
            continue;
        }

        $variant = $isValid($row['payment_variant'] ?? null)
            ? strtolower(trim((string) $row['payment_variant']))
            : null;

        if ($variant === null && $isBd($row['country'] ?? null)) {
            $variant = 'bd';
        }

        if ($variant === null) {
            // No signal at all. 'int' is the documented default; recorded here so
            // an operator can spot and correct these rows later.
            $variant = 'int';
            $ambiguous[] = "customer #{$row['id']} (country: " . (($row['country'] ?? '') ?: '—') . ')';
        }

        $stats['customers_updated']++;
        $projected['customers'][$variant]++;
        if ($apply) {
            $db->update('customers', ['variant' => $variant], 'id = ?', [(int) $row['id']]);
        }
    }

    $offset += $chunk;
} while (count($rows) === $chunk);

// -------------------------------------------------------------- licenses ----
$offset = 0;
do {
    $rows = $readLicenses($offset, $chunk);
    if ($rows === []) {
        break;
    }

    foreach ($rows as $row) {
        $stats['licenses_scanned']++;
        if ($isValid($row['variant'] ?? null)) {
            $stats['licenses_skipped']++;
            continue;
        }

        $variant = $isValid($row['customer_variant'] ?? null)
            ? strtolower(trim((string) $row['customer_variant']))
            : 'int';

        $stats['licenses_updated']++;
        $projected['licenses'][$variant]++;
        if ($apply) {
            $db->update('licenses', ['variant' => $variant], 'id = ?', [(int) $row['id']]);
        }
    }

    $offset += $chunk;
} while (count($rows) === $chunk);

// ---------------------------------------------------------------- report ----
foreach ($stats as $label => $value) {
    printf("  %-22s %d\n", str_replace('_', ' ', $label) . ':', $value);
}

echo "\nDistribution " . ($apply ? 'after' : 'if applied') . " backfill:\n";
foreach (['bd', 'int'] as $variant) {
    printf(
        "  %-4s customers=%-6d licenses=%d\n",
        strtoupper($variant),
        $projected['customers'][$variant],
        $projected['licenses'][$variant]
    );
}

if ($ambiguous !== []) {
    echo "\nRows resolved by the default (no payment history, non-BD country) — review these:\n";
    foreach (array_slice($ambiguous, 0, 50) as $line) {
        echo "  - {$line}\n";
    }
    if (count($ambiguous) > 50) {
        echo "  ... and " . (count($ambiguous) - 50) . " more\n";
    }
}

// Reconcile against `payments.variant`, the field that was already trustworthy
// before this column existed. Every customer the backfill resolved from payment
// history must agree with that payment's recorded market; a disagreement means
// the resolution order or the country list is wrong, so surface it loudly
// instead of letting a silent mis-bucket reach the dashboard.
$paymentMismatch = $db->fetchAll(
    "SELECT c.id, c.variant AS customer_variant, p.variant AS payment_variant
       FROM customers c
       JOIN payments p ON p.id = (
            SELECT id FROM payments
             WHERE customer_id = c.id AND variant IS NOT NULL
             ORDER BY id DESC LIMIT 1
       )
      WHERE c.variant IS NOT NULL
        AND LOWER(p.variant) IN ('bd', 'int')
        AND c.variant <> LOWER(p.variant)"
);

$bdPayments = (int) $db->count('payments', "LOWER(variant) = 'bd'");
$bdCustomers = (int) $db->count('customers', "variant = 'bd' AND deleted_at IS NULL");

echo "\nReconciliation against payments.variant:\n";
printf("  %-30s %d\n", 'BD payments on record:', $bdPayments);
printf("  %-30s %d\n", 'BD customers after backfill:', $bdCustomers);
printf(
    "  %-30s %d\n",
    'customers disagreeing with payment:',
    count($paymentMismatch)
);

if ($paymentMismatch !== []) {
    echo "\n  WARNING — these customers do not match their latest payment market:\n";
    foreach (array_slice($paymentMismatch, 0, 20) as $row) {
        printf(
            "    - customer #%d: customer=%s payment=%s\n",
            (int) $row['id'],
            (string) $row['customer_variant'],
            (string) $row['payment_variant']
        );
    }
    if (count($paymentMismatch) > 20) {
        echo '    ... and ' . (count($paymentMismatch) - 20) . " more\n";
    }
}

echo "\n" . ($apply
    ? "Done. Re-run with --dry-run to confirm a second pass changes nothing.\n"
    : "Dry run complete. Re-run with --apply to write these changes.\n");