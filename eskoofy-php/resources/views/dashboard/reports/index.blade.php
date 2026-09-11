@extends('layouts.dashboard')

@section('title', __('Reports'))

@section('content')
    <x-page-header :title="__('Reports')" :description="__('Pick a report to view live aggregations from the database.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Reports')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('dashboard.reports.fees') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-brand-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Financial report') }}</h2>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">$</span>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Revenue by month, status, and payment method.') }}</p>
            <p class="mt-4 text-sm font-medium text-brand-600 group-hover:text-brand-800 dark:text-brand-400 dark:group-hover:text-brand-300">{{ __('Open report') }} →</p>
        </a>

        <a href="{{ route('dashboard.reports.attendance') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-brand-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Attendance report') }}</h2>
                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">%</span>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Daily and per-class attendance over a date range.') }}</p>
            <p class="mt-4 text-sm font-medium text-brand-600 group-hover:text-brand-800 dark:text-brand-400 dark:group-hover:text-brand-300">{{ __('Open report') }} →</p>
        </a>

        <a href="{{ route('dashboard.reports.students') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-brand-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Students report') }}</h2>
                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-900/40 dark:text-violet-300">∑</span>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Enrolment by class, status, and gender.') }}</p>
            <p class="mt-4 text-sm font-medium text-brand-600 group-hover:text-brand-800 dark:text-brand-400 dark:group-hover:text-brand-300">{{ __('Open report') }} →</p>
        </a>

        <a href="{{ route('dashboard.analytics') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-brand-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Analytics') }}</h2>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Growth, fee target, attendance heatmap, teacher workload.') }}</p>
            <p class="mt-4 text-sm font-medium text-brand-600 group-hover:text-brand-800 dark:text-brand-400 dark:group-hover:text-brand-300">{{ __('Open analytics') }} →</p>
        </a>

        <a href="{{ route('dashboard.reports.builder') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-brand-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Report builder') }}</h2>
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">⚙</span>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Select fields and filters, then export a custom CSV.') }}</p>
            <p class="mt-4 text-sm font-medium text-brand-600 group-hover:text-brand-800 dark:text-brand-400 dark:group-hover:text-brand-300">{{ __('Build report') }} →</p>
        </a>
    </div>
@endsection