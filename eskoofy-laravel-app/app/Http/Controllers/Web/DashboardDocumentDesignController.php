<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DocumentDesign;
use App\Services\DocumentDesignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin CRUD for the per-document-type designs and their watermarks.
 *
 * The design is stored as a theme block (`settings`) plus a watermark block, both
 * normalised by DocumentDesignService before they are written, so what the admin
 * sees in the form is exactly what the printers read back — the service is the
 * single normaliser, never duplicated here.
 */
class DashboardDocumentDesignController extends Controller
{
    public function __construct(private DocumentDesignService $designs) {}

    public function index(Request $request): View
    {
        $this->authorize('manage_document_designs');

        $designs = DocumentDesign::query()
            ->orderBy('document_type')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->groupBy('document_type');

        return view('dashboard.document-designs.index', [
            'designs' => $designs,
            'types' => DocumentDesignService::types(),
            'active' => collect(DocumentDesignService::types())
                ->mapWithKeys(fn (string $type) => [$type => $this->designs->activeDesign($type)]),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('manage_document_designs');

        // The index links "Add" once per type, carrying ?document_type=… so the
        // form opens on the row the admin actually clicked. Without this the new
        // design would always start on certificate.
        $type = (string) $request->string('document_type', 'certificate');
        if (! DocumentDesignService::isType($type)) {
            $type = 'certificate';
        }

        return view('dashboard.document-designs.form', [
            'design' => new DocumentDesign([
                'document_type' => $type,
                'is_active' => true,
                'is_default' => false,
            ]),
            'types' => DocumentDesignService::types(),
            'templates' => DocumentDesignService::templates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage_document_designs');

        $validated = $this->validated($request);

        $design = DB::transaction(function () use ($validated) {
            $design = DocumentDesign::query()->create($validated);

            if ($design->is_default) {
                $this->demoteOtherDefaults($design->document_type, $design);
            }

            DocumentDesignService::flushCache();

            return $design;
        });

        return redirect()
            ->route('dashboard.document-designs.index')
            ->with('status', __('Document design saved.'));
    }

    public function edit(Request $request, DocumentDesign $documentDesign): View
    {
        $this->authorize('manage_document_designs');

        return view('dashboard.document-designs.form', [
            'design' => $documentDesign,
            'types' => DocumentDesignService::types(),
            'templates' => DocumentDesignService::templates(),
        ]);
    }

    public function update(Request $request, DocumentDesign $documentDesign): RedirectResponse
    {
        $this->authorize('manage_document_designs');

        $validated = $this->validated($request, $documentDesign);

        DB::transaction(function () use ($documentDesign, $validated) {
            $documentDesign->update($validated);

            if ($documentDesign->is_default) {
                $this->demoteOtherDefaults($documentDesign->document_type, $documentDesign);
            }

            DocumentDesignService::flushCache();
        });

        return redirect()
            ->route('dashboard.document-designs.index')
            ->with('status', __('Document design updated.'));
    }

    public function destroy(Request $request, DocumentDesign $documentDesign): RedirectResponse
    {
        $this->authorize('manage_document_designs');

        $documentDesign->delete();
        DocumentDesignService::flushCache();

        return back()->with('status', __('Document design deleted.'));
    }

    /**
     * Live preview: the CSS and watermark for a document type, rendered from the
     * *unsaved* form payload so the editor can re-render as the admin types. With
     * no payload it returns the currently stored design, which is what the
     * read-only preview link uses.
     */
    public function preview(Request $request)
    {
        $this->authorize('manage_document_designs');

        $type = (string) $request->string('document_type', 'certificate');
        if (! DocumentDesignService::isType($type)) {
            $type = 'certificate';
        }

        $unsaved = $request->has('settings') || $request->has('watermark');

        $theme = $unsaved
            ? array_merge($this->designs->theme($type), (array) $request->input('settings', []))
            : $this->designs->theme($type);
        $theme['document_type'] = $type;

        // `template` is a column of its own, not part of the settings blob, but it
        // lives at the top level of the posted form. Without folding it in here the
        // live preview would keep showing the *stored* template's CSS and the
        // editor would look broken whenever the template was changed.
        if ($request->filled('template') && DocumentDesignService::isTemplate((string) $request->input('template'))) {
            $theme['template'] = (string) $request->input('template');
        }

        $watermark = $unsaved
            ? array_merge($this->designs->watermark($type), (array) $request->input('watermark', []))
            : $this->designs->watermark($type);

        $customCss = $request->has('custom_css')
            ? (string) $request->input('custom_css')
            : (string) ($this->designs->activeDesign($type)?->custom_css ?? '');

        return response()->json([
            'type' => $type,
            'css' => $this->designs->cssFor($theme, $watermark, $customCss),
            'watermark' => $this->designs->sanitizeWatermark($watermark, $type),
            'theme' => $this->designs->sanitizeTheme($theme),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?DocumentDesign $design = null): array
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:'.implode(',', DocumentDesignService::types())],
            'name' => ['required', 'string', 'max:120'],
            'template' => ['required', 'string', 'in:'.implode(',', DocumentDesignService::templates())],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'custom_css' => ['nullable', 'string', 'max:20000'],
            'settings' => ['nullable', 'array'],
            'watermark' => ['nullable', 'array'],
        ]);

        return [
            'document_type' => $validated['document_type'],
            'name' => trim($validated['name']),
            'template' => $validated['template'],
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
            'custom_css' => $this->designs->sanitizeCss((string) ($validated['custom_css'] ?? '')),
            'settings' => $this->designs->sanitizeTheme((array) ($validated['settings'] ?? [])),
            'watermark' => $this->designs->sanitizeWatermark(
                (array) ($validated['watermark'] ?? []),
                $validated['document_type']
            ),
        ];
    }

    /**
     * Enforce one default per document type.
     *
     * Called from inside the caller's transaction: demoting the other rows is
     * part of the same change as saving this one, so a failure after this point
     * must not leave every default for the type cleared.
     */
    private function demoteOtherDefaults(string $type, ?DocumentDesign $design = null): void
    {
        DocumentDesign::query()
            ->where('document_type', $type)
            ->when($design !== null, fn ($q) => $q->whereKeyNot($design->getKey()))
            ->update(['is_default' => false]);
    }
}
