<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Core\DatabaseInterface;

/**
 * The dashboard activity timeline.
 *
 * `activity_logs` stores a dotted action key (`license.issued`,
 * `admin.updated_plan`, …) and an optional JSON `details` blob. This service maps
 * the key to a label, an icon and a severity tone so the view never carries a
 * 40-branch match statement, and degrades to a readable label for any key it has
 * not seen rather than rendering the raw key.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §6.3 (W21)
 */
final class ActivityFeed
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * @return list<array{action: string, label: string, icon: string, tone: string, actor: string, details: array<string, mixed>, created_at: string}>
     */
    public function recent(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));

        $rows = $this->db->fetchAll(
            'SELECT actor_type, actor_id, action, details, created_at
               FROM activity_logs
              ORDER BY id DESC
              LIMIT ' . $limit
        );

        $out = [];
        foreach ($rows as $row) {
            $action = (string) ($row['action'] ?? '');
            $meta = self::meta($action);

            $out[] = [
                'action'     => $action,
                'label'      => $meta['label'],
                'icon'       => $meta['icon'],
                'tone'       => $meta['tone'],
                'actor'      => (string) ($row['actor_type'] ?? 'system'),
                'details'    => self::decode($row['details'] ?? null),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Map an action key to presentation metadata.
     *
     * @return array{label: string, icon: string, tone: string}
     */
    public static function meta(string $action): array
    {
        $map = [
            'license.issued'          => ['label' => 'License issued',        'icon' => 'key',      'tone' => 'success'],
            'license.activated'       => ['label' => 'License activated',     'icon' => 'activity', 'tone' => 'info'],
            'license.deactivated'     => ['label' => 'License deactivated',   'icon' => 'activity', 'tone' => 'warning'],
            'license.renewed'         => ['label' => 'License renewed',       'icon' => 'repeat',   'tone' => 'success'],
            'license.reactivated'     => ['label' => 'License reactivated',   'icon' => 'repeat',   'tone' => 'success'],
            'payment.processed'       => ['label' => 'Payment processed',     'icon' => 'card',     'tone' => 'success'],
            'payment.pending'         => ['label' => 'Payment pending',       'icon' => 'card',     'tone' => 'warning'],
            'payment.approved'        => ['label' => 'Payment approved',      'icon' => 'card',     'tone' => 'success'],
            'customer.registered'     => ['label' => 'Customer registered',   'icon' => 'users',    'tone' => 'info'],
            'backup.created'          => ['label' => 'Backup created',        'icon' => 'archive',  'tone' => 'muted'],
            'backup.deleted'          => ['label' => 'Backup deleted',        'icon' => 'archive',  'tone' => 'danger'],
            'package.uploaded'        => ['label' => 'Package uploaded',      'icon' => 'archive',  'tone' => 'info'],
            'push_notification.sent'  => ['label' => 'Push sent',             'icon' => 'inbox',    'tone' => 'info'],
            'email.expiring_reminder' => ['label' => 'Expiry reminder sent',  'icon' => 'file',     'tone' => 'muted'],
        ];

        if (isset($map[$action])) {
            return $map[$action];
        }

        // `admin.<verb>_<noun>` → "Verb noun" with a neutral tone.
        if (str_starts_with($action, 'admin.')) {
            $verb = str_replace(['_', '.'], ' ', substr($action, 6));

            return ['label' => ucfirst(trim($verb)) ?: 'Admin action', 'icon' => 'tool', 'tone' => 'muted'];
        }

        return [
            'label' => $action !== '' ? ucfirst(str_replace(['_', '.'], ' ', $action)) : 'Activity',
            'icon'  => 'activity',
            'tone'  => 'muted',
        ];
    }

    /** @return array<string, mixed> */
    private static function decode(mixed $details): array
    {
        if (is_array($details)) {
            return $details;
        }
        if (is_string($details) && $details !== '') {
            $decoded = json_decode($details, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
