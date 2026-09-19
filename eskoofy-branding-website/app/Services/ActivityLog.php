<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class ActivityLog
{
    public static function log(
        string $action,
        string $actorType = 'system',
        ?int $actorId = null,
        array $details = []
    ): void {
        if (($_ENV['LICENSE_ACTIVITY_LOG'] ?? 'true') !== 'true') {
            return;
        }

        $detailsJson = [];
        foreach ($details as $key => $value) {
            $detailsJson[$key] = is_scalar($value) || $value === null ? $value : (string) json_encode($value);
        }

        Database::getInstance()->insert('activity_logs', [
            'actor_type' => $actorType,
            'actor_id'   => $actorId,
            'action'     => $action,
            'details'    => json_encode($detailsJson, JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}