@extends('layouts.dashboard')
@section('title', __('dashboard.library_reports') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.library_statistics') }}</h1>
</div>

@if(!isset($view))
<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-gray-500">{{ __('dashboard.total_books') }}</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalBooks }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-gray-500">{{ __('dashboard.issued') }}</p>
        <p class="mt-1 text-3xl font-bold text-yellow-600">{{ $issuedBooks }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-gray-500">{{ __('dashboard.overdue_books') }}</p>
        <p class="mt-1 text-3xl font-bold text-red-600">{{ $overdueBooks }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-gray-500">{{ __('dashboard.late_fee') }} ({{ __('dashboard.fine_paid') }})</p>
        <p class="mt-1 text-3xl font-bold text-green-600">{{ number_format($totalFines, 2) }}</p>
    </div>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="{{ route('dashboard.library.reports.issued') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-blue-300 hover:shadow-md transition">
        <p class="text-sm font-medium text-blue-600">{{ __('dashboard.currently_issued') }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $issuedBooks }}</p>
    </a>
    <a href="{{ route('dashboard.library.reports.overdue') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-red-300 hover:shadow-md transition">
        <p class="text-sm font-medium text-red-600">{{ __('dashboard.overdue_books') }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $overdueBooks }}</p>
    </a>
    <a href="{{ route('dashboard.library.reports.history') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-indigo-300 hover:shadow-md transition">
        <p class="text-sm font-medium text-indigo-600">{{ __('Full history') }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalIssues }}</p>
    </a>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-gray-500">{{ __('dashboard.lost') }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $lostBooks }}</p>
    </div>
</div>
@else
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h2 class="text-lg font-semibold text-gray-900">
        @switch($view)
            @case('issued'){{ __('dashboard.currently_issued') }}@break
            @case('overdue'){{ __('dashboard.overdue_books') }}@break
            @case('history'){{ __('Full history') }}@break
        @endswitch
    </h2>
    <a href="{{ route('dashboard.library.reports.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to reports') }}</a>
</div>

@if($view === 'history')
<form method="get" class="mb-4 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('dashboard.search_books') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px]">
    <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All status') }}</option>
        <option value="issued" @selected(request('status') === 'issued')>{{ __('dashboard.issued') }}</option>
        <option value="returned" @selected(request('status') === 'returned')>{{ __('dashboard.returned') }}</option>
        <option value="lost" @selected(request('status') === 'lost')>{{ __('dashboard.lost') }}</option>
        <option value="damaged" @selected(request('status') === 'damaged')>{{ __('dashboard.damaged') }}</option>
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('dashboard.filter') }}</button>
    <a href="{{ route('dashboard.library.reports.history') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">{{ __('dashboard.clear') }}</a>
</form>
@endif

<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.title') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.borrower') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.issue_date') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.due_date') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.return_date') }}</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">{{ __('Status') }}</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">{{ __('dashboard.late_fee') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($issues as $issue)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $issue->book?->title }}</td>
                    <td class="px-4 py-3">
                        @if($issue->student)
                            {{ trim($issue->student->first_name . ' ' . $issue->student->last_name) }}
                            <span class="text-xs text-gray-400">({{ __('Student') }})</span>
                        @elseif($issue->teacher)
                            {{ $issue->teacher->user?->name ?? $issue->teacher->employee_id }}
                            <span class="text-xs text-gray-400">({{ __('Teacher') }})</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $issue->issue_date->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ $issue->due_date->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ $issue->return_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $statusColors = ['issued' => 'bg-yellow-100 text-yellow-800', 'returned' => 'bg-green-100 text-green-800', 'lost' => 'bg-red-100 text-red-800', 'damaged' => 'bg-orange-100 text-orange-800'];
                            $color = $statusColors[$issue->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }}">{{ __($issue->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($issue->late_fee > 0)
                            <span class="font-medium text-red-600">{{ number_format($issue->late_fee, 2) }}</span>
                            @if($issue->fine_paid)<span class="ml-1 text-xs text-green-600">({{ __('dashboard.fine_paid') }})</span>@endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('No issues found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $issues->links() }}</div>
@endif
@endsection
