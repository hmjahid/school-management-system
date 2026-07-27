<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardNoticeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Notice::with('creator')->orderByDesc('pinned')->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            });
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('dashboard.notices.index', compact('rows'));
    }

    public function create(): View
    {
        return view('dashboard.notices.create', [
            'notice' => new Notice([
                'pinned' => false,
                'audience' => ['all'],
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateNotice($request);
        $data['created_by'] = $request->user()->id;
        $data['pinned'] = $request->boolean('pinned');
        $data['audience'] = $request->input('audience', ['all']);

        $notice = Notice::create($data);

        return redirect()
            ->route('dashboard.notices.edit', $notice)
            ->with('status', __('Notice saved.'));
    }

    public function edit(Notice $notice): View
    {
        return view('dashboard.notices.edit', compact('notice'));
    }

    public function update(Request $request, Notice $notice): RedirectResponse
    {
        $data = $this->validateNotice($request);
        $data['pinned'] = $request->boolean('pinned');
        $data['audience'] = $request->input('audience', ['all']);

        $notice->fill($data)->save();

        return back()->with('status', __('Notice updated.'));
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $notice->delete();

        return redirect()->route('dashboard.notices.index')->with('status', __('Notice deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateNotice(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'content_bn' => ['nullable', 'string'],
            'audience' => ['nullable', 'array'],
            'audience.*' => ['string'],
            'pinned' => ['nullable', 'boolean'],
        ]);
    }
}
