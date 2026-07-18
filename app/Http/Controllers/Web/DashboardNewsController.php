<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardNewsController extends Controller
{
    public function index(Request $request): View
    {
        $query = News::query()->orderByDesc('published_at')->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            }
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('dashboard.news.index', compact('rows'));
    }

    public function create(): View
    {
        return view('dashboard.news.create', [
            'news' => new News([
                'is_published' => false,
                'is_event' => false,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateNews($request);

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('website/news', 'public');
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title']);
        $data['published_at'] = $data['is_published'] ? ($data['published_at'] ?? now()) : null;

        $news = News::create($data);

        return redirect()
            ->route('dashboard.news.edit', $news)
            ->with('status', __('News saved.'));
    }

    public function edit(News $news): View
    {
        return view('dashboard.news.edit', compact('news'));
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        $data = $this->validateNews($request, $news->id);

        if ($request->hasFile('image')) {
            if ($news->image_url && str_starts_with($news->image_url, 'website/news/')) {
                Storage::disk('public')->delete($news->image_url);
            }
            $data['image_url'] = $request->file('image')->store('website/news', 'public');
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title'], $news->id);
        $data['published_at'] = $data['is_published'] ? ($data['published_at'] ?? ($news->published_at ?? now())) : null;

        $news->fill($data)->save();

        return back()->with('status', __('News updated.'));
    }

    public function destroy(News $news): RedirectResponse
    {
        if ($news->image_url && str_starts_with($news->image_url, 'website/news/')) {
            Storage::disk('public')->delete($news->image_url);
        }

        $news->delete();

        return redirect()->route('dashboard.news.index')->with('status', __('News deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateNews(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'is_event' => ['nullable', 'boolean'],
            'event_date' => ['nullable', 'date'],
            'event_location' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:6144'],
        ]) + [
            'is_published' => $request->has('is_published'),
            'is_event' => $request->has('is_event'),
        ];
    }

    protected function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: Str::slug(Str::random(8));
        $slug = $base;
        $i = 2;

        while (
            News::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
