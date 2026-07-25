@extends('layouts.dashboard')

@section('title', __('dashboard.visitor_logs') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ __('dashboard.visitor_logs') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.visitor_logs_description') }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.total_visits') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($totalVisits) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.unique_visitors') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($uniqueVisitors) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.today_visits') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($todayVisits) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.authenticated_visits') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($authenticatedVisits) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <form method="get" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('dashboard.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('dashboard.search_ip_url_user') }}"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('dashboard.from') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('dashboard.to') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
            </div>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('dashboard.filter') }}</button>
            @if(request()->hasAny(['search', 'date_from', 'date_to']))
                <a href="{{ route('dashboard.visitor-logs.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ __('dashboard.clear') }}</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('dashboard.time') }}</th>
                        <th class="px-4 py-3">{{ __('dashboard.ip_address') }}</th>
                        <th class="px-4 py-3">{{ __('dashboard.user') }}</th>
                        <th class="px-4 py-3">{{ __('dashboard.url') }}</th>
                        <th class="px-4 py-3">{{ __('dashboard.referer') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                {{ $log->created_at?->format('M j, Y g:i A') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $log->ip }}</td>
                            <td class="px-4 py-3">
                                @if($log->user)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-900/20 dark:text-brand-400">
                                        {{ $log->user->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 text-xs" title="{{ $log->url }}">{{ $log->url }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-xs text-slate-400" title="{{ $log->referer }}">{{ $log->referer ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">{{ __('dashboard.no_visitor_logs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-700">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
