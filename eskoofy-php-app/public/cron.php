<?php
/**
 * Cron entry point for the Eskoofy raw-PHP port.
 *
 * Mirrors the app's scheduler (routes/console.php):
 *   - payments:process-recurring   -> daily (01:00)
 *   - notifications:process-scheduled --force -> every 5 minutes
 *
 * Configure a single cron line in your hosting panel:
 *   * * * * *  php /path/to/public/cron.php >/dev/null 2>&1
 *
 * The script self-gates by time-of-day and every-5-minute windows, so a single
 * per-minute cron entry is sufficient.
 *
 * Usage (for manual testing):
 *   php public/cron.php --recurring
 *   php public/cron.php --notifications
 *
 * @package Eskoofy
 */

declare(strict_types=1);

$appRoot = dirname(__DIR__);
require $appRoot . '/app/Core/bootstrap.php';

$only = $argv[1] ?? '';
$minute = (int) date('i');
$hour   = (int) date('G');

$ran = false;

// 1) Recurring payments — once daily at 01:00.
if (($only === '' || $only === '--recurring') && ($hour === 1 && $minute === 0)) {
    try {
        $service = new \App\Services\RecurringPaymentService();
        $result = $service->processDuePayments();
        error_log('[cron:recurring] processed=' . $result['processed']
            . ' succeeded=' . $result['succeeded'] . ' failed=' . $result['failed']);
    } catch (\Throwable $e) {
        error_log('[cron:recurring] error: ' . $e->getMessage());
    }
    $ran = true;
}

// 2) Scheduled notifications — every 5 minutes.
if (($only === '' || $only === '--notifications') && $minute % 5 === 0) {
    try {
        $service = new \App\Services\Notification\ScheduledNotificationService();
        $processed = $service->processDueNotifications(10);
        if ($processed > 0) {
            error_log('[cron:notifications] processed=' . $processed);
        }
    } catch (\Throwable $e) {
        error_log('[cron:notifications] error: ' . $e->getMessage());
    }
    $ran = true;
}

if ($only !== '' && !$ran) {
    // Explicit flag but outside its window — report the gating so the operator
    // knows the invocation was valid but intentionally skipped.
    fwrite(STDERR, "[cron] {$only} outside its schedule window (skipped).\n");
    exit(0);
}

exit(0);