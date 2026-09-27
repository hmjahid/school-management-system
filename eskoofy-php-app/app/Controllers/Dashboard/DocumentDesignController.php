<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Gate;
use App\Core\Request;
use App\Core\Session;
use App\Models\DocumentDesign;
use App\Services\DocumentDesignService;

/**
 * Admin CRUD for the per-document-type designs and their watermarks.
 *
 * The design is stored as a theme block (`settings`) plus a watermark block, both
 * normalised by DocumentDesignService before they are written, so what the admin
 * sees in the form is exactly what the printers read back — the service is the
 * single normaliser, never duplicated here.
 *
 * Port of eskoofy-laravel-app/app/Http/Controllers/Web/DashboardDocumentDesignController.php.
 */
class DocumentDesignController extends Controller
{
    private DocumentDesignService $designs;

    private DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->designs = new DocumentDesignService();
    }

    public function index(): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_document_designs'), 403);

        $designs = new \App\Core\Support\Collection();
        foreach (DocumentDesign::query()
            ->orderBy('document_type')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get() as $design) {
            $designs->push($design);
        }
        $grouped = $designs->groupBy('document_type');

        $active = [];
        foreach (DocumentDesignService::types() as $type) {
            $active[$type] = $this->designs->activeDesign($type);
        }

        $this->view('dashboard.document-designs.index', [
            'designs' => $grouped,
            'types' => DocumentDesignService::types(),
            'active' => $active,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_document_designs'), 403);

        $type = (string) ($_GET['document_type'] ?? 'certificate');
        if (! DocumentDesignService::isType($type)) {
            $type = 'certificate';
        }

        $this->view('dashboard.document-designs.form', [
            'design' => new DocumentDesign([
                'document_type' => $type,
                'is_active' => true,
                'is_default' => false,
            ]),
            'types' => DocumentDesignService::types(),
            'templates' => DocumentDesignService::templates(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_document_designs'), 403);

        $validated = $this->validated();

        $design = DocumentDesign::create($validated);

        if ($design->is_default) {
            $this->demoteOtherDefaults($design->document_type, $design);
        }

        DocumentDesignService::flushCache();

        Session::getInstance()->flash('success', __('Document design saved.'));
        $this->redirect(route('dashboard.document-designs.index'));
    }

    public function edit(string $id): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_document_designs'), 403);

        $design = DocumentDesign::findOrFail((int) $id);

        $this->view('dashboard.document-designs.form', [
            'design' => $design,
            'types' => DocumentDesignService::types(),
            'templates' => DocumentDesignService::templates(),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_document_designs'), 403);

        $design = DocumentDesign::findOrFail((int) $id);
        $validated = $this->validated();

        $design->update($validated);

        if ($design->is_default) {
            $this->demoteOtherDefaults($design->document_type, $design);
        }

        DocumentDesignService::flushCache();

        Session::getInstance()->flash('success', __('Document design updated.'));
        $this->redirect(route('dashboard.document-designs.index'));
    }

    public function destroy(string $id): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_document_designs'), 403);

        $design = DocumentDesign::findOrFail((int) $id);
        $design->delete();
        DocumentDesignService::flushCache();

        Session::getInstance()->flash('success', __('Document design deleted.'));
        $this->back();
    }

    /**
     * Live preview: the CSS and watermark for a document type, rendered from the
     * *unsaved* form payload so the editor can re-render as the admin types. With
     * no payload it returns the currently stored design, which is what the
     * read-only preview link uses.
     */
    public function preview(): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_document_designs'), 403);

        $type = (string) ($_GET['document_type'] ?? $_POST['document_type'] ?? 'certificate');
        if (! DocumentDesignService::isType($type)) {
            $type = 'certificate';
        }

        $hasPayload = isset($_POST['settings']) || isset($_POST['watermark']) || isset($_POST['template']);

        $theme = $hasPayload
            ? array_merge($this->designs->theme($type), (array) ($_POST['settings'] ?? []))
            : $this->designs->theme($type);
        $theme['document_type'] = $type;

        if (! empty($_POST['template']) && DocumentDesignService::isTemplate((string) $_POST['template'])) {
            $theme['template'] = (string) $_POST['template'];
        }

        $watermark = $hasPayload
            ? array_merge($this->designs->watermark($type), (array) ($_POST['watermark'] ?? []))
            : $this->designs->watermark($type);

        $customCss = isset($_POST['custom_css'])
            ? (string) $_POST['custom_css']
            : (string) ($this->designs->activeDesign($type)?->custom_css ?? '');

        $this->json([
            'type' => $type,
            'css' => $this->designs->cssFor($theme, $watermark, $customCss),
            'watermark' => $this->designs->sanitizeWatermark($watermark, $type),
            'theme' => $this->designs->sanitizeTheme($theme),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(): array
    {
        $validated = $this->validate([
            'document_type' => 'required|in:' . implode(',', DocumentDesignService::types()),
            'name' => 'required|max:120',
            'template' => 'required|in:' . implode(',', DocumentDesignService::templates()),
            'custom_css' => 'max:20000',
        ]);

        $type = (string) $validated['document_type'];

        return [
            'document_type' => $type,
            'name' => trim((string) $validated['name']),
            'template' => (string) $validated['template'],
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'custom_css' => $this->designs->sanitizeCss((string) ($validated['custom_css'] ?? '')),
            'settings' => $this->designs->sanitizeTheme((array) ($_POST['settings'] ?? [])),
            'watermark' => $this->designs->sanitizeWatermark((array) ($_POST['watermark'] ?? []), $type),
        ];
    }

    private function demoteOtherDefaults(string $type, ?DocumentDesign $design = null): void
    {
        $this->db->update(
            'document_designs',
            ['is_default' => 0],
            'document_type = ?' . ($design !== null ? ' AND id != ?' : ''),
            $design !== null ? [$type, $design->getKey()] : [$type]
        );
    }
}