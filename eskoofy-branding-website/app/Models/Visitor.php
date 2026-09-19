<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Public-site visitor analytics. Unlike the other models this table has no
 * soft-delete column, so the query helpers are written explicitly rather than
 * relying on the shared Model base class.
 */
class Visitor
{
    public static function record(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return Database::getInstance()->insert('visitors', [
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'path'       => (string) ($data['path'] ?? '/'),
            'referrer'   => $data['referrer'] ?? null,
            'country'    => $data['country'] ?? null,
            'locale'     => $data['locale'] ?? null,
            'is_bot'     => !empty($data['is_bot']) ? 1 : 0,
            'visited_at' => $data['visited_at'] ?? $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @return array<string, int|float> */
    public static function kpis(): array
    {
        $row = Database::getInstance()->fetch(
            "SELECT
                (SELECT COUNT(*) FROM visitors) AS total_views,
                (SELECT COUNT(DISTINCT ip_address) FROM visitors) AS total_unique,
                (SELECT COUNT(*) FROM visitors WHERE visited_at >= CURDATE()) AS today_views,
                (SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE visited_at >= CURDATE()) AS today_unique,
                (SELECT COUNT(*) FROM visitors WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS week_views,
                (SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS week_unique,
                (SELECT COUNT(*) FROM visitors WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS month_views,
                (SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS month_unique,
                (SELECT COUNT(*) FROM visitors WHERE is_bot = 1) AS bot_views"
        );

        $defaults = [
            'total_views' => 0, 'total_unique' => 0, 'today_views' => 0, 'today_unique' => 0,
            'week_views' => 0, 'week_unique' => 0, 'month_views' => 0, 'month_unique' => 0,
            'bot_views' => 0,
        ];

        return array_map('intval', array_merge($defaults, $row ?? []));
    }

    /** @return array<int, array{label: string, value: int}> */
    public static function trend(int $days = 30): array
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT DATE(visited_at) AS d, COUNT(*) AS c
             FROM visitors
             WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL " . max(1, $days - 1) . " DAY)
             GROUP BY DATE(visited_at)"
        );

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string) $row['d']] = (int) $row['c'];
        }

        $out = [];
        $cursor = new \DateTimeImmutable('today');
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $cursor->modify("-{$i} days");
            $out[] = [
                'label' => $day->format($days > 14 ? 'j M' : 'D'),
                'value' => $byDay[$day->format('Y-m-d')] ?? 0,
            ];
        }

        return $out;
    }

    /** @return array<int, array{path: string, c: int}> */
    public static function topPaths(int $limit = 10): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT path, COUNT(*) AS c FROM visitors
             GROUP BY path ORDER BY c DESC LIMIT " . max(1, $limit)
        );
    }

    /** @return array<int, array{country: string, c: int}> */
    public static function topCountries(int $limit = 8): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT country, COUNT(*) AS c FROM visitors
             WHERE country IS NOT NULL AND country <> ''
             GROUP BY country ORDER BY c DESC LIMIT " . max(1, $limit)
        );
    }

    /**
     * @param array{path?: string, bots?: bool} $filters
     * @return array{data: array<int, array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public static function paginate(int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($filters['path'])) {
            $where .= ' AND path LIKE ?';
            $params[] = '%' . $filters['path'] . '%';
        }
        if (array_key_exists('bots', $filters) && $filters['bots'] === false) {
            $where .= ' AND is_bot = 0';
        }

        $db = Database::getInstance();
        $total = $db->count('visitors', $where, $params);
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $rows = $db->fetchAll(
            "SELECT * FROM visitors WHERE {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) max(1, ceil($total / $perPage)),
        ];
    }
}
