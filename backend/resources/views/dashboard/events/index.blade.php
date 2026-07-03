@extends('layouts.dashboard')

@section('title', __('Events'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Events') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('School events, open days, sports days, and more.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dashboard.events.calendar') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Calendar view') }}</a>
            <a href="{{ route('dashboard.events.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('New event') }}</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form method="get" class="mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Search') }}</label>
            <input type="search" name="search" value="{{ request('search') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="{{ __('Title, location…') }}">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Status') }}</label>
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">{{ __('Any') }}</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Filter') }}</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Title') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('When') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Location') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($events as $event)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $event->title }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ optional($event->start_date)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $event->location ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">{{ ucfirst($event->status ?? '—') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('dashboard.events.edit', $event) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ __('Edit') }}</a>
                                <form method="post" action="{{ route('dashboard.events.destroy', $event) }}" class="inline" onsubmit="return confirm({{ json_encode(__('Delete this event?')) }});">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 font-medium text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No events found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $events->links() }}</div>
        @endif
    </div>
@endsection
