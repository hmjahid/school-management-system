<?php
declare(strict_types=1);

namespace App\Services;

/**
 * The single definition of the admin navigation tree.
 *
 * Both the sidebar (`views/layouts/admin.php`) and the Ctrl+K command palette
 * render from this, so a route can never appear in one and be missing from the
 * other. Labels are lang keys under `admin.nav.*`; `label` is the English
 * fallback used before translations are wired through.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §5.2, §9
 */
final class Nav
{
    /**
     * @return array<string, array{key: string, items: list<array<string, mixed>>}>
     *         Group title => items. Item keys: label, href, icon, badge, match.
     */
    public static function groups(?string $path = null, array $badges = []): array
    {
        $path = $path ?? (string) ($_SERVER['REQUEST_URI'] ?? '/');

        $groups = [
            'admin.nav.group_overview' => [
                self::item('dashboard', 'Dashboard', '/admin', 'dashboard', $path, ['/admin', '/admin/dashboard']),
            ],
            'admin.nav.group_sales' => [
                self::item('licenses', 'Licenses', '/admin/licenses', 'key', $path, ['/admin/licenses']),
                self::item('plans', 'Plans', '/admin/plans', 'layers', $path, ['/admin/plans']),
                self::item('payments', 'Payments', '/admin/payments', 'card', $path, ['/admin/payments']),
                self::item('subscriptions', 'Subscriptions', '/admin/subscriptions', 'repeat', $path, ['/admin/subscriptions']),
                self::item('packages', 'Packages', '/admin/packages', 'archive', $path, ['/admin/packages']),
                self::item('gateways', 'Payment gateways', '/admin/gateways', 'card', $path, ['/admin/gateways']),
                self::item('services', 'Deployment & maintenance', '/admin/services', 'tool', $path, ['/admin/services']),
            ],
            'admin.nav.group_analytics' => [
                self::item('analytics', 'Analytics', '/admin/analytics', 'chart', $path, ['/admin/analytics']),
            ],
            'admin.nav.group_customers' => [
                self::item('customers', 'All customers', '/admin/customers', 'users', $path, ['/admin/customers']),
            ],
            'admin.nav.group_content' => [
                self::item('pages', 'Pages', '/admin/pages', 'layout', $path, ['/admin/pages']),
                self::item('posts', 'Blog posts', '/admin/posts', 'file', $path, ['/admin/posts']),
                self::item('post_categories', 'Post categories', '/admin/post-categories', 'tag', $path, ['/admin/post-categories']),
                self::item('custom_requests', 'Custom orders', '/admin/custom-requests', 'inbox', $path, ['/admin/custom-requests']),
                self::item('messages', 'Messages', '/admin/messages', 'inbox', $path, ['/admin/messages']),
            ],
            'admin.nav.group_system' => [
                self::item('visitors', 'Visitor log', '/admin/visitors', 'eye', $path, ['/admin/visitors']),
                self::item('activities', 'Activity log', '/admin/activities', 'activity', $path, ['/admin/activities']),
                self::item('email_templates', 'Email templates', '/admin/email-templates', 'file', $path, ['/admin/email-templates']),
                self::item('client_documents', 'Client documents', '/admin/client-documents', 'file', $path, ['/admin/client-documents']),
                self::item('push_notifications', 'Push notifications', '/admin/push-notifications', 'inbox', $path, ['/admin/push-notifications']),
                self::item('cache', 'Clear cache', '/admin/cache', 'sliders', $path, ['/admin/cache']),
                self::item('backup', 'Backups', '/admin/backup', 'archive', $path, ['/admin/backup', '/admin/cloud-backup']),
                self::item('settings', 'Settings', '/admin/settings', 'sliders', $path, ['/admin/settings']),
                self::item('account', 'My account', '/admin/account', 'user', $path, ['/admin/account']),
            ],
        ];

        // Attach live unread badges.
        $badgeMap = [
            'messages'        => (int) ($badges['messages'] ?? 0),
            'custom_requests' => (int) ($badges['custom_requests'] ?? 0),
        ];

        foreach ($groups as &$items) {
            foreach ($items as $i => $item) {
                if (($badgeMap[$item['key']] ?? 0) > 0) {
                    $items[$i]['badge'] = $badgeMap[$item['key']];
                }
            }
        }
        unset($items);

        return $groups;
    }

    /**
     * Flat list of every navigable destination — the command palette's
     * "Navigate" group and the sidebar both project from this.
     *
     * @return list<array<string, mixed>>
     */
    public static function destinations(?string $path = null, array $badges = []): array
    {
        $out = [];
        foreach (self::groups($path, $badges) as $groupKey => $items) {
            foreach ($items as $item) {
                $item['group'] = $groupKey;
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * "Quick create" entries shown under the sales group and in the palette's
     * Create section.
     *
     * @return list<array<string, mixed>>
     */
    public static function createActions(): array
    {
        return [
            ['key' => 'create_license',  'label' => 'Issue license',  'href' => '/admin/licenses/create',        'icon' => 'key'],
            ['key' => 'create_plan',     'label' => 'Add plan',       'href' => '/admin/plans/create',           'icon' => 'layers'],
            ['key' => 'create_post',     'label' => 'New post',       'href' => '/admin/posts/create',           'icon' => 'file'],
            ['key' => 'create_category', 'label' => 'New category',   'href' => '/admin/post-categories/create', 'icon' => 'tag'],
            ['key' => 'create_page',     'label' => 'New page',       'href' => '/admin/pages/create',           'icon' => 'layout'],
        ];
    }

    /**
     * Destructive/system actions exposed in the palette's Actions section.
     * Each is a POST + CSRF + confirm step in the UI.
     *
     * @return list<array<string, mixed>>
     */
    public static function systemActions(): array
    {
        return [
            ['key' => 'clear_cache',     'label' => 'Clear cache',        'href' => '/admin/cache',            'method' => 'POST', 'confirm' => true],
            ['key' => 'create_backup',   'label' => 'Create full backup', 'href' => '/admin/backup/create/full', 'method' => 'POST', 'confirm' => true],
            ['key' => 'run_cloud_backup','label' => 'Run cloud backup',   'href' => '/admin/cloud-backup/run', 'method' => 'POST', 'confirm' => true],
        ];
    }

    /**
     * Build one nav item.
     *
     * @param list<string> $match URL prefixes that mark this item active.
     * @return array<string, mixed>
     */
    private static function item(string $key, string $label, string $href, string $icon, string $path, array $match): array
    {
        return [
            'key'    => $key,
            'label'  => $label,
            'href'   => $href,
            'icon'   => $icon,
            'match'  => $match,
            'active' => self::isActive($path, $match),
        ];
    }

    /** @param list<string> $match */
    private static function isActive(string $path, array $match): bool
    {
        $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: '/', '/');

        foreach ($match as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/')) {
                return true;
            }
        }

        // `/admin` and `/admin/dashboard` are the same destination.
        if ($path === '/admin' && in_array('/admin/dashboard', $match, true)) {
            return true;
        }

        return false;
    }
}