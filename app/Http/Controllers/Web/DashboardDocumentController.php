<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebsiteDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardDocumentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $query = WebsiteDocument::query()->orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        $rows = $query->paginate(25)->withQueryString();
        $categories = WebsiteDocument::query()->select('category')->distinct()->orderBy('category')->pluck('category')->filter();

        return view('dashboard.documents.index', compact('rows', 'categories'));
    }

    public function create(): View
    {
        return view('dashboard.documents.create', ['document' => new WebsiteDocument(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'is_published' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store('website/documents', 'public');

        $doc = WebsiteDocument::create([
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'is_published' => $request->has('is_published'),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return redirect()->route('dashboard.documents.edit', $doc)->with('status', __('Document saved.'));
    }

    public function edit(WebsiteDocument $document): View
    {
        return view('dashboard.documents.edit', compact('document'));
    }

    public function update(Request $request, WebsiteDocument $document): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'is_published' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        if ($request->hasFile('file')) {
            if ($document->file_path && str_starts_with($document->file_path, 'website/documents/')) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('file');
            $path = $file->store('website/documents', 'public');
            $document->file_path = $path;
            $document->mime_type = $file->getClientMimeType();
            $document->file_size = $file->getSize();
        }

        $document->title = $data['title'];
        $document->category = $data['category'] ?? null;
        $document->is_published = $request->has('is_published');
        $document->save();

        return back()->with('status', __('Document updated.'));
    }

    public function destroy(Request $request, WebsiteDocument $document): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        if ($document->file_path && str_starts_with($document->file_path, 'website/documents/')) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('dashboard.documents.index')->with('status', __('Document deleted.'));
    }
}
