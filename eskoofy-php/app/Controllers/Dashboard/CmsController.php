<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class CmsController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $pages = $this->db->fetchAll(
            "SELECT id, page, title, title_en, title_bn, is_active, updated_at
             FROM website_contents
             ORDER BY page ASC"
        );

        $this->view('dashboard.cms.index', ['pages' => $pages]);
    }

    public function edit(?string $page = ''): void
    {
        Auth::requireAuth();
        $page = trim((string) $page);

        $content = \App\Models\WebsiteContent::query()->where('page', $page)->first();
        if (!$content) {
            $title = \App\Core\Support\Str::title(str_replace('-', ' ', $page !== '' ? $page : 'home'));
            $content = new \App\Models\WebsiteContent([
                'page'       => $page !== '' ? $page : 'home',
                'title'      => $title,
                'title_en'   => $title,
                'title_bn'   => $title,
                'content'    => [],
                'content_en' => [],
                'content_bn' => [],
                'is_active'  => true,
            ]);
        }

        $registry = [];
        if (is_file(base_path('config/cms_pages.php'))) {
            $registry = (array) require base_path('config/cms_pages.php');
        }
        $def = $registry[$page] ?? [
            'label'       => $content->title ?: \App\Core\Support\Str::title(str_replace('-', ' ', $page)),
            'description' => '',
            'sections'    => [],
        ];

        $visibilityRegistry = [];
        if (is_file(base_path('config/cms_section_visibility.php'))) {
            $visibilityRegistry = (array) require base_path('config/cms_section_visibility.php');
        }
        $visibilityKeys = $visibilityRegistry[$page] ?? [];

        $settings = \App\Models\WebsiteSetting::query()->first();
        $sectionVis = is_array($settings->section_visibility ?? null) ? $settings->section_visibility : [];

        $this->view('dashboard.cms.edit', [
            'content'        => $content,
            'page'           => (string) ($content->page ?? $page),
            'def'            => $def,
            'values'         => [
                'en' => $content->englishContentTree(),
                'bn' => $content->bengaliContentTree(),
            ],
            'visibilityKeys' => $visibilityKeys,
            'sectionVis'     => $sectionVis,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'page'              => 'required|max:191',
            'title'             => 'max:191',
            'title_en'          => 'max:191',
            'title_bn'          => 'max:191',
            'content'           => 'max:65535',
            'content_en'        => 'max:65535',
            'content_bn'        => 'max:65535',
            'meta_description'  => 'max:500',
            'meta_description_en' => 'max:500',
            'meta_description_bn' => 'max:500',
            'meta_keywords'     => 'max:500',
            'is_active'         => 'in:0,1',
        ]);

        $existing = $this->db->fetch("SELECT id FROM website_contents WHERE id = ?", [$id]);
        if (!$existing) {
            Session::getInstance()->flash('error', 'Page not found.');
            $this->redirect('/dashboard/cms');
            return;
        }

        $payload = [
            'page'             => $data['page'],
            'title'            => $data['title'] ?? null,
            'title_en'         => $data['title_en'] ?? null,
            'title_bn'         => $data['title_bn'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_description_en' => $data['meta_description_en'] ?? null,
            'meta_description_bn' => $data['meta_description_bn'] ?? null,
            'meta_keywords'    => $data['meta_keywords'] ?? null,
            'is_active'        => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        foreach (['content', 'content_en', 'content_bn'] as $k) {
            if (isset($data[$k])) {
                $payload[$k] = json_encode(['text' => $data[$k]]);
            }
        }

        $this->db->update('website_contents', $payload, 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Page saved.');
        $this->redirect('/dashboard/cms');
    }
}
