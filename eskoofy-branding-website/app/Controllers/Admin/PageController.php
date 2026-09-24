<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

class PageController extends Controller
{
    private const LOCALES = ['en', 'bn'];
    private const FIELDS = ['title', 'heading', 'meta_title'];
    private const MAX = ['title' => 191, 'heading' => 191, 'meta_title' => 191];

    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT id, name, route, sort_order, status, noindex, updated_at
             FROM pages WHERE deleted_at IS NULL
             ORDER BY sort_order ASC, id ASC"
        );

        $this->view('admin.pages', ['admin' => Auth::user(), 'pages' => $rows]);
    }

    public function create(): void
    {
        $this->view('admin.page-form', ['admin' => Auth::user(), 'page' => null]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'  => 'required|max:191',
            'route' => 'required|max:191',
            'status' => 'required|in:active,draft',
        ]);

        $route = $this->uniqueRoute($data['route'], null);

        $id = Database::getInstance()->insert('pages', $this->payload($data, $route, null));

        ActivityLog::log('admin.created_page', 'admin', (int) Auth::id(), ['id' => $id, 'route' => $route]);

        $this->withSuccess('Page created.');
        $this->redirect('/admin/pages');
    }

    public function edit(int $id): void
    {
        $page = Database::getInstance()->fetch("SELECT * FROM pages WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$page) {
            $this->withError('Page not found.');
            $this->redirect('/admin/pages');
        }

        $this->view('admin.page-form', ['admin' => Auth::user(), 'page' => $page]);
    }

    public function update(int $id): void
    {
        $page = Database::getInstance()->fetch("SELECT id, route FROM pages WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$page) {
            $this->withError('Page not found.');
            $this->redirect('/admin/pages');
        }

        $data = $this->validate([
            'name'   => 'required|max:191',
            'route'  => 'required|max:191',
            'status' => 'required|in:active,draft',
        ]);

        $route = $this->uniqueRoute($data['route'], (int) $id);

        Database::getInstance()->update('pages', $this->payload($data, $route, (int) $id), 'id = ?', [$id]);

        ActivityLog::log('admin.updated_page', 'admin', (int) Auth::id(), ['id' => $id, 'route' => $route]);

        $this->withSuccess('Page updated.');
        $this->redirect('/admin/pages');
    }

    public function delete(int $id): void
    {
        $page = Database::getInstance()->fetch("SELECT id FROM pages WHERE id = ?", [$id]);
        if (!$page) {
            $this->withError('Page not found.');
            $this->redirect('/admin/pages');
        }

        Database::getInstance()->update('pages', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.deleted_page', 'admin', (int) Auth::id(), ['id' => $id]);

        $this->withSuccess('Page deleted.');
        $this->redirect('/admin/pages');
    }

    /**
     * Assemble the full insert/update column set from the POST body.
     *
     * @param array<string, string> $data validated core fields
     */
    private function payload(array $data, string $route, ?int $ignoreId): array
    {
        $payload = [
            'name'       => $data['name'],
            'route'      => $route,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'status'     => $data['status'],
            'noindex'    => isset($_POST['noindex']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        foreach (self::LOCALES as $locale) {
            foreach ([...self::FIELDS, ...['meta_description', 'intro', 'content']] as $field) {
                $value = trim((string) ($_POST[$field . '_' . $locale] ?? ''));
                if ($value === '') {
                    $value = null;
                } elseif (isset(self::MAX[$field])) {
                    $value = mb_substr($value, 0, self::MAX[$field]);
                } elseif ($field === 'meta_description') {
                    $value = mb_substr($value, 0, 255);
                }
                $payload[$field . '_' . $locale] = $value;
            }
        }

        foreach (['canonical', 'hreflang_en', 'hreflang_bn'] as $field) {
            $value = trim((string) ($_POST[$field] ?? ''));
            $payload[$field] = $value !== '' ? mb_substr($value, 0, 255) : null;
        }

        $schema = trim((string) ($_POST['json_schema'] ?? ''));
        // Validate JSON-LD before storing so a broken schema never reaches the page.
        if ($schema !== '' && json_decode($schema, true) === null) {
            $this->withError('The JSON-LD schema is not valid JSON.');
            $this->redirect($ignoreId === null ? '/admin/pages/create' : '/admin/pages/' . $ignoreId . '/edit');
        }
        $payload['json_schema'] = $schema !== '' ? $schema : null;

        if ($ignoreId === null) {
            $payload['created_at'] = date('Y-m-d H:i:s');
        }

        return $payload;
    }

    private function uniqueRoute(string $route, ?int $ignoreId): string
    {
        $route = '/' . trim($route, '/');
        $row = Database::getInstance()->fetch("SELECT id FROM pages WHERE route = ? LIMIT 1", [$route]);

        if (!$row || ($ignoreId !== null && (int) $row['id'] === $ignoreId)) {
            return $route;
        }

        $base = $route;
        $i = 1;
        while ($row) {
            $route = $base . '-' . $i++;
            $row = Database::getInstance()->fetch("SELECT id FROM pages WHERE route = ? LIMIT 1", [$route]);
            if ($ignoreId !== null && $row && (int) $row['id'] === $ignoreId) {
                return $route;
            }
            if ($i > 50) {
                return $base . '-' . bin2hex(random_bytes(3));
            }
        }

        return $route;
    }
}