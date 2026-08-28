@extends('layouts.dashboard')

@section('title', __('Reports'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Reports') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Pick a report to view live aggregations from the database.') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('dashboard.reports.fees') }}" class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Financial report') }}</h2>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">$</span>
            </div>
            <p class="mt-2 text-sm text-gray-600">{{ __('Revenue by month, status, and payment method.') }}</p>
            <p class="mt-4 text-sm font-medium text-blue-600 group-hover:text-blue-800">{{ __('Open report') }} →</p>
        </a>

        <a href="{{ route('dashboard.reports.attendance') }}" class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Attendance report') }}</h2>
                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800">%</span>
            </div>
            <p class="mt-2 text-sm text-gray-600">{{ __('Daily and per-class attendance over a date range.') }}</p>
            <p class="mt-4 text-sm font-medium text-blue-600 group-hover:text-blue-800">{{ __('Open report') }} →</p>
        </a>

        <a href="{{ route('dashboard.reports.students') }}" class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Students report') }}</h2>
                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800">∑</span>
            </div>
            <p class="mt-2 text-sm text-gray-600">{{ __('Enrolment by class, status, and gender.') }}</p>
            <p class="mt-4 text-sm font-medium text-blue-600 group-hover:text-blue-800">{{ __('Open report') }} →</p>
        <a href="{{ route('dashboard.analytics') }}" class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Analytics') }}</h2>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">📊</span>
            </div>
            <p class="mt-2 text-sm text-gray-600">{{ __('Growth, fee target, attendance heatmap, teacher workload.') }}</p>
            <p class="mt-4 text-sm font-medium text-blue-600 group-hover:text-blue-800">{{ __('Open analytics') }} →</p>
        </a>

        <a href="{{ route('dashboard.reports.builder') }}" class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Report builder') }}</h2>
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">⚙</span>
            </div>
            <p class="mt-2 text-sm text-gray-600">{{ __('Select fields and filters, then export a custom CSV.') }}</p>
            <p class="mt-4 text-sm font-medium text-blue-600 group-hover:text-blue-800">{{ __('Build report') }} →</p>
        </a>
    </div>
@endsection
