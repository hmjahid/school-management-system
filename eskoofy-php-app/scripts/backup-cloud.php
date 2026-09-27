<?php
/**
 * CLI cloud-backup runner for the raw-PHP port (parity with Laravel
 * `php artisan backup:cloud`).
 *
 *   php scripts/backup-cloud.php [--dispatch] [--restore=<file>] [--list]
 *
 * No flags  → upload a fresh portable backup now (with retention pruning).
 * --dispatch → run the interval dispatcher (used by cron.php as well).
 * --list     → print the provider's remote files.
 * --restore=<file> → download and restore a remote copy.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

$appRoot = dirname(__DIR__);
require $appRoot . '/app/Core/bootstrap.php';

use App\Core\Database;
use App\Services\CloudBackup\CloudBackupManager;
use App\Services\CloudBackup\CloudBackupService;

$service = new CloudBackupService(new CloudBackupManager(), Database::getInstance());
$args = $argv[1] ?? '';

if ($args === '--list') {
    $listing = $service->listRemote();
    echo ($listing['ok'] ? 'OK: ' : 'ERROR: ') . $listing['message'] . "\n";
    foreach ($listing['files'] as $file) {
        printf("  %-40s %8d bytes  %s\n", $file['name'], $file['size'], date('Y-m-d H:i:s', $file['modified']));
    }
    exit($listing['ok'] ? 0 : 1);
}

if (str_starts_with($args, '--restore=')) {
    $file = substr($args, strlen('--restore='));
    $result = $service->restore($file);
    echo ($result['ok'] ? 'OK: ' : 'ERROR: ') . $result['message'] . "\n";
    exit($result['ok'] ? 0 : 1);
}

if ($args === '--dispatch') {
    $result = $service->dispatch();
    echo $result['status'] . ': ' . $result['message'] . "\n";
    exit($result['status'] === 'success' ? 0 : 1);
}

$result = $service->run();
echo $result['status'] . ': ' . $result['message'] . "\n";
exit($result['status'] === 'success' ? 0 : 1);
