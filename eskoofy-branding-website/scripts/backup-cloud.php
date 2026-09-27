<?php
/**
 * CLI cloud-backup runner for the branding website.
 *
 *   php scripts/backup-cloud.php            # upload a fresh portable backup now
 *   php scripts/backup-cloud.php --dispatch # interval dispatcher (cron)
 *
 * Add a cron line on shared hosting:
 *   * * * * * php /path/scripts/backup-cloud.php --dispatch >/dev/null 2>&1
 *
 * @package Eskoofy
 */

declare(strict_types=1);

$appRoot = dirname(__DIR__);
require $appRoot . '/app/Core/bootstrap.php';

use App\Core\Database;
use App\Services\CloudBackupService;

$service = new CloudBackupService(Database::getInstance());
$args = $argv[1] ?? '';

$result = $args === '--dispatch' ? $service->dispatch() : $service->run();

echo $result['status'] . ': ' . $result['message'] . "\n";
exit($result['status'] === 'success' ? 0 : 1);
