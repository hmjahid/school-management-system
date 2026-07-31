<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebsiteMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardMediaController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->can('manage_media'), 403);

        $query = WebsiteMedia::query()->orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('file_path', 'like', "%{$search}%");
            });
        }

        $rows = $query->paginate(24)->withQueryString();
        $categories = WebsiteMedia::query()->select('category')->distinct()->orderBy('category')->pluck('category')->filter();

        return view('dashboard.media.index', compact('rows', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->can('manage_media'), 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('media', 'public');

        WebsiteMedia::create([
            'title' => $data['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'category' => $data['category'] ?? null,
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('status', __('Media uploaded.'));
    }

    public function download(Request $request, WebsiteMedia $media)
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->can('manage_media'), 403);

        if (! Storage::disk('public')->exists($media->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($media->file_path);
    }

    public function destroy(Request $request, WebsiteMedia $media): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->can('manage_media'), 403);

        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        return back()->with('status', __('Media deleted.'));
    }
}
