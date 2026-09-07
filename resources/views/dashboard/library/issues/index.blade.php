@extends('layouts.dashboard')
@section('title', __('dashboard.book_issues') . ' — ' . config('app.name'))

@section('content')
    @php
        $statusVariants = [
            'issued' => 'warning',
            'returned' => 'success',
            'lost' => 'danger',
            'damaged' => 'default',
        ];
    @endphp

    <x-page-header :title="__('dashboard.book_issues')" :description="__('Track borrowed books, due dates, and fines.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Library')],
                ['label' => __('dashboard.book_issues')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('issue_books')
                <x-button :href="route('dashboard.library.issues.create')">{{ __('dashboard.issue_book') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 flex flex-wrap items-end gap-2">
        <div class="min-w-[220px] flex-1">
            <label for="filter-search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('dashboard.search_books') }}</label>
            <input id="filter-search" name="search" value="{{ request('search') }}" placeholder="{{ __('dashboard.search_books') }}" class="admin-input">
        </div>
        <div>
            <label for="filter-status" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Status') }}</label>
            <select id="filter-status" name="status" class="admin-select">
                <option value="">{{ __('All status') }}</option>
                <option value="issued" @selected(request('status') === 'issued')>{{ __('dashboard.issued') }}</option>
                <option value="returned" @selected(request('status') === 'returned')>{{ __('dashboard.returned') }}</option>
                <option value="lost" @selected(request('status') === 'lost')>{{ __('dashboard.lost') }}</option>
                <option value="damaged" @selected(request('status') === 'damaged')>{{ __('dashboard.damaged') }}</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <x-button type="submit" size="sm">{{ __('dashboard.filter') }}</x-button>
            <x-button :href="route('dashboard.library.issues.index')" variant="secondary" size="sm">{{ __('dashboard.clear') }}</x-button>
        </div>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('dashboard.title')],
            ['label' => __('dashboard.borrower')],
            ['label' => __('dashboard.issue_date')],
            ['label' => __('dashboard.due_date')],
            ['label' => __('dashboard.return_date')],
            ['label' => __('Status'), 'class' => 'text-center'],
            ['label' => __('dashboard.late_fee'), 'class' => 'text-right'],
            ['label' => __('Actions')],
        ]"
        :paginator="$issues"
        empty-icon="inbox"
        :empty-title="__('No issues found.')"
        :empty-message="__('Issued books will appear here with their due dates.')"
    >
        @forelse($issues as $issue)
            <tr class="admin-table-row">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $issue->book?->title }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                    @if($issue->student)
                        {{ trim($issue->student->first_name . ' ' . $issue->student->last_name) }}
                        <span class="text-xs text-slate-400 dark:text-slate-500">({{ __('Student') }})</span>
                    @elseif($issue->teacher)
                        {{ $issue->teacher->user?->name ?? $issue->teacher->employee_id }}
                        <span class="text-xs text-slate-400 dark:text-slate-500">({{ __('Teacher') }})</span>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $issue->issue_date->format('d M Y') }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $issue->due_date->format('d M Y') }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $issue->return_date?->format('d M Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <x-badge :variant="$statusVariants[$issue->status] ?? 'default'">{{ __($issue->status) }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @if($issue->late_fee > 0)
                        <span class="font-medium text-red-600 dark:text-red-400">{{ number_format($issue->late_fee, 2) }}</span>
                        @if($issue->fine_paid)<span class="ml-1 text-xs text-green-600 dark:text-green-400">({{ __('dashboard.fine_paid') }})</span>@endif
                    @else
                        <span class="text-slate-400 dark:text-slate-500">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-button :href="route('dashboard.library.issues.show', $issue)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection