@extends('layouts.dashboard')

@section('title', __('Documents') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Documents') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Upload files (policies, forms, magazine/newsletters) and link them from CMS pages.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="get" class="flex items-center gap-2">
                <select name="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('dashboard.documents.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('New') }}</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Category') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Published') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('File') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $d)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $d->title }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $d->category ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if($d->is_published)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Yes') }}</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ __('No') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            <a class="text-blue-600 hover:underline" href="{{ asset('storage/' . $d->file_path) }}" target="_blank" rel="noreferrer">{{ __('Open') }}</a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('dashboard.documents.edit', $d) }}" class="text-blue-600 hover:underline">{{ __('Edit') }}</a>
                            <form method="post" action="{{ route('dashboard.documents.destroy', $d) }}" class="inline" onsubmit="return confirm('{{ __('Delete this item?') }}')">
                                @csrf
                                @method('delete')
                                <button class="ml-3 text-red-600 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No documents yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
@endsection

