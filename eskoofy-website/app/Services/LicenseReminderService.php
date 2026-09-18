<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Sends "license expiring soon" reminders for active licenses that are about
 * to lapse. Idempotent per license per day: a reminder is only sent once,
 * tracked via an activity-log entry for that license + date.
 */
class LicenseReminderService
{
    public function __construct(private int $days = 14)
    {
    }

    /**
     * @return int number of reminder emails actually sent
     */
    public function notifyExpiring(): int
    {
        $db = Database::getInstance();
        $sent = 0;

        try {
            $rows = $db->fetchAll(
                "SELECT l.id, l.license_key, l.expires_at, c.id AS customer_id, c.name AS customer_name, c.email AS customer_email
                 FROM licenses l
                 JOIN customers c ON l.customer_id = c.id
                 WHERE l.deleted_at IS NULL
                   AND l.status = 'active'
                   AND l.expires_at IS NOT NULL
                   AND l.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                   AND c.deleted_at IS NULL
                   AND c.email IS NOT NULL
                 ORDER BY l.expires_at ASC",
                [$this->days]
            );

            foreach ($rows as $license) {
                $licenseId = (int) $license['id'];
                $today = date('Y-m-d');

                $already = $db->fetch(
                    "SELECT id FROM activity_logs
                     WHERE action = 'email.expiring_reminder'
                       AND actor_type = 'system'
                       AND details LIKE ?
                     LIMIT 1",
                    ['%"license_id":' . $licenseId . ',%"date":"' . $today . '%']
                );
                if ($already) {
                    continue;
                }

                $base = (string) ($_ENV['APP_URL'] ?? 'http://localhost:8011');
                Mailer::sendView(
                    (string) $license['customer_email'],
                    'Your Eskoofy license is expiring soon',
                    'license_expiring',
                    [
                        'name'       => $license['customer_name'] ?: 'there',
                        'licenseKey' => $license['license_key'],
                        'expiresAt'  => $license['expires_at'],
                        'renewUrl'   => rtrim($base, '/') . '/account/licenses/' . $licenseId,
                    ]
                );

                ActivityLog::log('email.expiring_reminder', 'system', (int) $license['customer_id'], [
                    'license_id' => $licenseId,
                    'license_key' => $license['license_key'],
                    'expires_at' => $license['expires_at'],
                    'date'       => $today,
                ]);
                $sent++;
            }
        } catch (\Throwable) {
            // Non-blocking: a reminder failure must not break the dashboard.
        }

        return $sent;
    }
}