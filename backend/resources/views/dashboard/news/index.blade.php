@extends('layouts.dashboard')

@section('title', __('News') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('News & Events') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Create and publish website news articles and events.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="get" class="flex flex-wrap items-center gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search...') }}"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="published" @selected(request('status') === 'published')>{{ __('Published') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('Draft') }}</option>
                </select>
                <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ __('Filter') }}</button>
            </form>
            <a href="{{ route('dashboard.news.create') }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                {{ __('New') }}
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Category') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Published') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $row->title }}</div>
                            <div class="text-xs text-gray-500">{{ $row->slug }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $row->category ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if($row->is_published)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Published') }}</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ __('Draft') }}</span>
                            @endif
                            @if($row->is_event)
                                <span class="ml-2 rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">{{ __('Event') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $row->published_at?->format('Y-m-d') ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-blue-600 hover:underline" href="{{ route('dashboard.news.edit', $row) }}">{{ __('Edit') }}</a>
                            <form method="post" action="{{ route('dashboard.news.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this item?') }}')">
                                @csrf
                                @method('delete')
                                <button class="ml-3 text-red-600 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No news yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
@endsection

