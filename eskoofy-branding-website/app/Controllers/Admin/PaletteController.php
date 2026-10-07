<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\Nav;

/**
 * Data source for the Ctrl+K command palette.
 *
 * Returns grouped, permission-scoped results: navigation destinations (from the
 * same {@see Nav} tree that renders the sidebar), create actions, system actions,
 * and a debounced record search across customers / licenses / payments.
 *
 * Read-only. The "system actions" group only *navigates* to the page that owns
 * the action, because the palette link handler is a plain GET — the actual
 * mutation stays a POST + CSRF on its own controller.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §9
 */
class PaletteController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $needle = $q === '' ? '' : mb_strtolower($q);

        $groups = [];

        // Navigate — every destination, filtered by label.
        $nav = [];
        foreach (Nav::destinations() as $dest) {
            if ($needle === '' || str_contains(mb_strtolower((string) $dest['label']), $needle)) {
                $nav[] = ['label' => (string) $dest['label'], 'href' => (string) $dest['href']];
            }
        }
        if ($nav !== []) {
            $groups[] = ['group' => 'Navigate', 'items' => $nav];
        }

        // Create — the quick-create shortcuts.
        $create = [];
        foreach (Nav::createActions() as $action) {
            if ($needle === '' || str_contains(mb_strtolower((string) $action['label']), $needle)) {
                $create[] = ['label' => (string) $action['label'], 'href' => (string) $action['href']];
            }
        }
        if ($create !== []) {
            $groups[] = ['group' => 'Create', 'items' => $create];
        }

        // Records — only worth querying once the query is meaningful.
        if (mb_strlen($q) >= 2) {
            foreach ($this->records($q) as $group) {
                if ($group['items'] !== []) {
                    $groups[] = $group;
                }
            }
        }

        // Quick actions — jump to the page that owns the action.
        $actions = [
            ['label' => 'Clear cache', 'href' => '/admin/cache'],
            ['label' => 'Backups', 'href' => '/admin/backup'],
            ['label' => 'Payment gateways', 'href' => '/admin/gateways'],
            ['label' => 'Email templates', 'href' => '/admin/email-templates'],
        ];
        $filtered = [];
        foreach ($actions as $action) {
            if ($needle === '' || str_contains(mb_strtolower($action['label']), $needle)) {
                $filtered[] = $action;
            }
        }
        if ($filtered !== []) {
            $groups[] = ['group' => 'Actions', 'items' => $filtered];
        }

        $this->json(['success' => true, 'message' => 'OK', 'data' => $groups]);
    }

    /**
     * Jump-to-record search across the three record types.
     *
     * @return list<array{group: string, items: list<array{label: string, href: string, hint: string}>}>
     */
    private function records(string $q): array
    {
        $like = '%' . $q . '%';
        $db = Database::getInstance();

        $out = [];

        try {
            $customers = $db->fetchAll(
                'SELECT id, name, email FROM customers
                  WHERE deleted_at IS NULL AND (name LIKE ? OR email LIKE ?)
                  ORDER BY id DESC LIMIT 8',
                [$like, $like]
            );
        } catch (\Throwable) {
            $customers = [];
        }
        $out[] = ['group' => 'Customers', 'items' => array_map(static fn (array $r): array => [
            'label' => (string) $r['name'],
            'hint'  => (string) $r['email'],
            'href'  => '/admin/customers/' . (int) $r['id'],
        ], $customers)];

        try {
            $licenses = $db->fetchAll(
                'SELECT l.id, l.license_key, c.name AS customer_name
                   FROM licenses l LEFT JOIN customers c ON c.id = l.customer_id
                  WHERE l.deleted_at IS NULL AND (l.license_key LIKE ? OR c.name LIKE ?)
                  ORDER BY l.id DESC LIMIT 8',
                [$like, $like]
            );
        } catch (\Throwable) {
            $licenses = [];
        }
        $out[] = ['group' => 'Licenses', 'items' => array_map(static fn (array $r): array => [
            'label' => (string) $r['license_key'],
            'hint'  => (string) ($r['customer_name'] ?? ''),
            'href'  => '/admin/licenses/' . (int) $r['id'],
        ], $licenses)];

        try {
            $payments = $db->fetchAll(
                "SELECT p.id, p.reference, p.amount, p.currency, c.name AS customer_name
                   FROM payments p LEFT JOIN customers c ON c.id = p.customer_id
                  WHERE p.reference LIKE ? OR c.name LIKE ?
                  ORDER BY p.id DESC LIMIT 8",
                [$like, $like]
            );
        } catch (\Throwable) {
            $payments = [];
        }
        $out[] = ['group' => 'Payments', 'items' => array_map(static fn (array $r): array => [
            'label' => (string) ($r['reference'] ?? ('#' . $r['id'])),
            'hint'  => (string) ($r['customer_name'] ?? '') . ' · ' . (string) ($r['currency'] ?? 'USD') . ' ' . number_format((float) $r['amount'], 2),
            'href'  => '/admin/payments',
        ], $payments)];

        return $out;
    }
}
