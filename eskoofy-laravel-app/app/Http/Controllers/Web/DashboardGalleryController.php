<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardGalleryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Gallery::query()->orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $rows = $query->paginate(24)->withQueryString();
        $categories = Gallery::query()->select('category')->distinct()->orderBy('category')->pluck('category');

        return view('dashboard.gallery.index', compact('rows', 'categories'));
    }

    public function create(): View
    {
        return view('dashboard.gallery.create', [
            'gallery' => new Gallery(['is_published' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGallery($request);
        $data['is_published'] = $request->has('is_published');

        $data['image_path'] = $request->file('image')->store('website/gallery', 'public');

        $gallery = Gallery::create($data);

        return redirect()
            ->route('dashboard.gallery.edit', $gallery)
            ->with('status', __('Gallery item saved.'));
    }

    public function edit(Gallery $gallery): View
    {
        return view('dashboard.gallery.edit', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        $data = $this->validateGallery($request, false);
        $data['is_published'] = $request->has('is_published');

        if ($request->hasFile('image')) {
            if ($gallery->image_path && str_starts_with($gallery->image_path, 'website/gallery/')) {
                Storage::disk('public')->delete($gallery->image_path);
            }
            $data['image_path'] = $request->file('image')->store('website/gallery', 'public');
        }

        $gallery->fill($data)->save();

        return back()->with('status', __('Gallery item updated.'));
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        if ($gallery->image_path && str_starts_with($gallery->image_path, 'website/gallery/')) {
            Storage::disk('public')->delete($gallery->image_path);
        }

        $gallery->delete();

        return redirect()->route('dashboard.gallery.index')->with('status', __('Gallery item deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateGallery(Request $request, bool $requireImage = true): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:120'],
            'is_published' => ['nullable', 'boolean'],
            'image' => array_values(array_filter([
                $requireImage ? 'required' : 'nullable',
                'image',
                'max:6144',
            ])),
        ]);
    }
}
