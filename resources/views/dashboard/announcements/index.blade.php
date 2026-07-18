@extends('layouts.dashboard')

@section('title', __('Announcements') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Announcements') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Announcements shown in the student/parent portal.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="get" class="flex items-center gap-2">
                <select name="audience" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All audiences') }}</option>
                    <option value="all" @selected(request('audience') === 'all')>{{ __('All') }}</option>
                    <option value="student" @selected(request('audience') === 'student')>{{ __('Students') }}</option>
                    <option value="parent" @selected(request('audience') === 'parent')>{{ __('Parents') }}</option>
                </select>
            </form>
            <a href="{{ route('dashboard.announcements.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('New') }}</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Audience') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Active window') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Published') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $row->title }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $row->audience }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $row->starts_at?->format('Y-m-d') ?? '—' }} → {{ $row->ends_at?->format('Y-m-d') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($row->is_published)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Yes') }}</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ __('No') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('dashboard.announcements.edit', $row) }}" class="text-blue-600 hover:underline">{{ __('Edit') }}</a>
                            <form method="post" action="{{ route('dashboard.announcements.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this item?') }}')">
                                @csrf
                                @method('delete')
                                <button class="ml-3 text-red-600 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No announcements yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
@endsection

