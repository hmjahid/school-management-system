<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\Analytics\LicenseService;

class License extends Model
{
    protected static string $table = 'licenses';
    protected static bool $softDeletes = true;

    /**
     * Licenses expiring within `$days`, restricted to genuinely usable rows.
     *
     * The shell (sidebar pin + attention card) needs this on every admin page,
     * so it lives on the model beside the other shell counts — but the
     * predicate is {@see LicenseService::activeSql()}, the one canonical
     * expression the dashboard KPI and the expiring list already share (bug
     * B1: the tile and the table must never disagree).
     */
    public static function countExpiringSoon(int $days = 30): int
    {
        $days = max(1, min(365, $days));

        return (int) static::db()->fetch(
            'SELECT COUNT(*) AS total FROM licenses l
              WHERE ' . LicenseService::activeSql('l') . '
                AND l.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ' . $days . ' DAY)'
        )['total'];
    }
}
