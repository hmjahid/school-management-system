@extends('layouts.dashboard')

@section('title', __('Dashboard') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $trendBars = [];
        foreach (($attendanceStats['trend'] ?? []) as $day) {
            $trendBars[] = max(8, min(100, (int) ($day['rate'] ?? 0)));
        }
        if (empty($trendBars)) { $trendBars = [0, 0, 0, 0, 0, 0, 0]; }

        $months = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $revenueData = [85000, 92000, 88000, 95000, 91000, 98000, 102000, 96000, 99000, 105000, 101000, 108000];
        $expenseData = [62000, 58000, 64000, 61000, 67000, 63000, 69000, 65000, 71000, 68000, 72000, 70000];
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Dashboard') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Welcome back,') }} {{ auth()->user()->name }}!</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.bulk') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('Import') }}
            </a>
            <a href="{{ route('dashboard.reports') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                {{ __('Reports') }}
            </a>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <a href="{{ route('dashboard.students') }}" class="admin-stat-card block">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Students') }}</p>
                <span class="rounded-full bg-brand-50 p-1.5 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ number_format($stats['totalStudents'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Active enrollment') }}</p>
            @if(($stats['pendingAdmissions'] ?? 0) > 0)
                <a href="{{ route('dashboard.admissions.index') }}" class="mt-3 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400">
                    {{ __(':n pending admissions', ['n' => number_format((int) ($stats['pendingAdmissions'] ?? 0))]) }}
                </a>
            @endif
        </a>
        <a href="{{ route('dashboard.teachers') }}" class="admin-stat-card block">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Teachers') }}</p>
                <span class="rounded-full bg-emerald-50 p-1.5 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ number_format($stats['totalTeachers'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Full-time staff') }}</p>
        </a>
        <a href="{{ route('dashboard.parents') }}" class="admin-stat-card block">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Parents') }}</p>
                <span class="rounded-full bg-amber-50 p-1.5 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ number_format($stats['totalParents'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Registered guardians') }}</p>
        </a>
        <a href="{{ route('dashboard.attendance') }}" class="admin-stat-card block">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Attendance') }}</p>
                <span class="rounded-full bg-sky-50 p-1.5 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $stats['attendanceRate'] ?? 0 }}%</p>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Last 7 days') }}</p>
        </a>
        <a href="{{ route('dashboard.fee-payments.index') }}" class="admin-stat-card block">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Revenue') }}</p>
                <span class="rounded-full bg-violet-50 p-1.5 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ number_format($stats['totalRevenue'] ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Total collected') }}</p>
            @if(($stats['pendingDues'] ?? 0) > 0)
                <span class="mt-3 inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    {{ __(':n pending dues', ['n' => number_format((float) ($stats['pendingDues'] ?? 0), 2)]) }}
                </span>
            @endif
        </a>
    </div>

    <div class="mb-8 grid gap-6 lg:grid-cols-2">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Revenue vs Expenses') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Last 12 months') }}</span>
            </div>
            <div class="admin-card-body">
                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 mb-4">
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-4 rounded bg-emerald-500"></span> {{ __('Revenue') }}</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-4 rounded bg-red-400"></span> {{ __('Expenses') }}</span>
                </div>
                <div class="relative h-48">
                    <svg viewBox="0 0 600 200" class="h-full w-full" preserveAspectRatio="none">
                        @foreach([0, 50, 100, 150, 200] as $y)
                            <line x1="0" y1="{{ $y }}" x2="600" y2="{{ $y }}" stroke="oklch(0.9 0 0 / 0.3)" stroke-width="0.5"/>
                        @endforeach
                        <path d="M{{ implode(' ', array_map(function($i, $v) use ($revenueData) { $x = ($i + 0.5) * 600 / 12; $y = 200 - ($v / 120000 * 200); return $i === 0 ? 'M' . $x . ' ' . $y : 'L' . $x . ' ' . $y; }, array_keys($revenueData), $revenueData)) }} L600 200 L0 200 Z" fill="oklch(0.65 0.18 160 / 0.15)" stroke="oklch(0.55 0.18 160)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                        <path d="M{{ implode(' ', array_map(function($i, $v) use ($expenseData) { $x = ($i + 0.5) * 600 / 12; $y = 200 - ($v / 120000 * 200); return $i === 0 ? 'M' . $x . ' ' . $y : 'L' . $x . ' ' . $y; }, array_keys($expenseData), $expenseData)) }} L600 200 L0 200 Z" fill="oklch(0.65 0.18 25 / 0.15)" stroke="oklch(0.55 0.18 25)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                    <div class="mt-1 flex justify-between text-[0.6rem] text-slate-400 dark:text-slate-500">
                        @foreach($months as $m)
                            <span>{{ $m }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __("Today's Attendance") }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Real-time') }}</span>
            </div>
            <div class="admin-card-body">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900/30 dark:bg-emerald-900/20">
                        <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-400">{{ __('Present') }}</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ $attendanceStats['present_today'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/20">
                        <p class="text-xs uppercase tracking-wide text-red-700 dark:text-red-400">{{ __('Absent') }}</p>
                        <p class="mt-1 text-3xl font-bold text-red-700 dark:text-red-300">{{ $attendanceStats['absent_today'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 dark:border-amber-900/30 dark:bg-amber-900/20">
                        <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ __('Late') }}</p>
                        <p class="mt-1 text-3xl font-bold text-amber-700 dark:text-amber-300">{{ $attendanceStats['late_today'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg border border-sky-100 bg-sky-50 p-4 dark:border-sky-900/30 dark:bg-sky-900/20">
                        <p class="text-xs uppercase tracking-wide text-sky-700 dark:text-sky-400">{{ __('On leave') }}</p>
                        <p class="mt-1 text-3xl font-bold text-sky-700 dark:text-sky-300">{{ $attendanceStats['leave_today'] ?? 0 }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-700">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __("Today's rate") }}</span>
                    <span class="text-2xl font-bold text-brand-600 dark:text-brand-400">{{ $attendanceStats['today_rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="admin-card lg:col-span-2">
            <div class="admin-card-header">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Attendance Trend') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Last 7 days') }}</span>
            </div>
            <div class="admin-card-body">
                <div class="flex h-44 items-end justify-between gap-2 rounded-lg bg-slate-50 p-4 dark:bg-slate-800/50">
                    @foreach ($trendBars as $h)
                        <div class="group relative w-full">
                            <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition-all duration-300 hover:from-brand-500 hover:to-brand-300" style="height: {{ $h }}%"></div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 rounded bg-slate-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100">{{ round($h) }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Quick Actions') }}</h2>
            </div>
            <div class="admin-card-body space-y-2">
                <a href="{{ route('dashboard.students.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/></svg>
                    </span>
                    {{ __('Add Student') }}
                </a>
                <a href="{{ route('dashboard.teachers.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
                    </span>
                    {{ __('Add Teacher') }}
                </a>
                <a href="{{ route('dashboard.attendance.bulk') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                    </span>
                    {{ __('Mark Attendance') }}
                </a>
                <a href="{{ route('dashboard.fees') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/></svg>
                    </span>
                    {{ __('Collect Fees') }}
                </a>
                <a href="{{ route('dashboard.exams') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                    </span>
                    {{ __('Manage Exams') }}
                </a>
            </div>
        </div>
    </div>
@endsection
