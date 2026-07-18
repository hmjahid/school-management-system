@extends('layouts.dashboard')

@section('title', __('Dashboard') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $iconColors = [
            'brand' => 'bg-brand-50 text-brand-600',
            'emerald' => 'bg-emerald-50 text-emerald-600',
            'amber' => 'bg-amber-50 text-amber-600',
            'sky' => 'bg-sky-50 text-sky-600',
            'violet' => 'bg-violet-50 text-violet-600',
        ];
        $trendBars = [];
        foreach (($attendanceStats['trend'] ?? []) as $day) {
            $trendBars[] = max(8, min(100, (int) ($day['rate'] ?? 0)));
        }
        if (empty($trendBars)) {
            $trendBars = [0, 0, 0, 0, 0, 0, 0];
        }
    @endphp

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Total students') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['totalStudents']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Teachers') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['totalTeachers']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Parents') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['totalParents']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Attendance (7 days)') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $stats['attendanceRate'] }}%</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Fee revenue') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['totalRevenue'], 2) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('Attendance trend') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('Last 7 days.') }}</p>
            <div class="flex h-44 items-end justify-between gap-2 rounded-lg bg-slate-50 p-4">
                @foreach ($trendBars as $h)
                    <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400" style="height: {{ $h }}%"></div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __("Today's attendance") }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('Real-time counts.') }}</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-emerald-700">{{ __('Present') }}</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-700">{{ $attendanceStats['present_today'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-red-100 bg-red-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-red-700">{{ __('Absent') }}</p>
                    <p class="mt-1 text-3xl font-bold text-red-700">{{ $attendanceStats['absent_today'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-amber-700">{{ __('Late') }}</p>
                    <p class="mt-1 text-3xl font-bold text-amber-700">{{ $attendanceStats['late_today'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-sky-100 bg-sky-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-sky-700">{{ __('On leave') }}</p>
                    <p class="mt-1 text-3xl font-bold text-sky-700">{{ $attendanceStats['leave_today'] ?? 0 }}</p>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-sm text-slate-600">{{ __("Today's rate") }}</span>
                <span class="text-2xl font-bold text-brand-600">{{ $attendanceStats['today_rate'] ?? 0 }}%</span>
            </div>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('dashboard.attendance.bulk') }}" class="inline-flex flex-1 items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Bulk mark') }}</a>
                <a href="{{ route('dashboard.attendance') }}" class="inline-flex flex-1 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Records') }}</a>
            </div>
        </div>
    </div>
@endsection