<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CmsWebController extends Controller
{
    public function pages(): View
    {
        $pages = WebsiteContent::query()->orderBy('page')->paginate(20);

        return view('dashboard.cms.pages', compact('pages'));
    }

    public function edit(string $page): View
    {
        $content = WebsiteContent::where('page', $page)->first();

        if (! $content) {
            $content = new WebsiteContent([
                'page' => $page,
                'title' => Str::title(str_replace('-', ' ', $page)),
                'content' => [],
                'is_active' => true,
            ]);
        }

        $raw = $content->content ?? [];
        if (! is_array($raw)) {
            $raw = ['body' => $raw];
        }
        $json = json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';

        return view('dashboard.cms.edit', [
            'content' => $content,
            'contentJson' => $json,
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'content_json' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $decoded = json_decode($validated['content_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return back()->withErrors(['content_json' => __('Content must be valid JSON (object or array).')])->withInput();
        }

        WebsiteContent::updateOrCreate(
            ['page' => $page],
            [
                'title' => $validated['title'],
                'content' => $decoded,
                'meta_description' => $validated['meta_description'] ?? null,
                'meta_keywords' => $validated['meta_keywords'] ?? null,
                'is_active' => $request->has('is_active'),
            ]
        );

        return redirect()
            ->route('dashboard.cms.edit', ['page' => $page])
            ->with('status', __('Page saved.'));
    }
}
