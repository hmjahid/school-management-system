@extends('layouts.dashboard')

@section('title', __('Analytics') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('dashboard.analytics')" description="{{ __('dashboard.insights') }}">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('dashboard.analytics')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    @php
        $maxChartValue = max(1, max(array_merge($revenue, $expenses)));
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="admin-card">
            <div class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('dashboard.fee_target_monthly') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($feeTarget, 2) }}</p>
            </div>
        </div>
        <div class="admin-card">
            <div class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('dashboard.collected_this_month') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($feeCollected, 2) }}</p>
            </div>
        </div>
        <div class="admin-card">
            <div class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('dashboard.target_reached') }}</p>
                <p class="mt-2 text-2xl font-bold {{ $feeTargetPercent >= 75 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $feeTargetPercent }}%</p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                    <div class="h-full rounded-full {{ $feeTargetPercent >= 75 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ min(100, $feeTargetPercent) }}%"></div>
                </div>
            </div>
        </div>
        <div class="admin-card">
            <div class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('dashboard.active_classes') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $attendanceByClass->count() }}</p>
            </div>
        </div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.student_growth') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Last 12 months') }}</span>
            </div>
            <div class="admin-card-body">
                <div class="flex h-48 items-end justify-between gap-2 rounded-lg bg-slate-50 p-4 dark:bg-slate-800/50">
                    @foreach ($studentGrowth as $count)
                        <div class="group relative w-full">
                            <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition-all" style="height: {{ max(4, min(100, $count > 0 ? 100 : 0)) }}%"></div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 rounded bg-slate-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100">{{ number_format($count) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-1 flex justify-between text-[0.6rem] text-slate-400 dark:text-slate-500">
                    @foreach($months as $m)
                        <span>{{ $m }}</span>
                    @endforeach
                </div>
            </div>
        </div>

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
                        <path d="M{{ implode(' ', array_map(function($i, $v) use ($revenue, $maxChartValue) { $x = ($i + 0.5) * 600 / 12; $y = 200 - ($v / $maxChartValue * 200); return $i === 0 ? 'M' . $x . ' ' . $y : 'L' . $x . ' ' . $y; }, array_keys($revenue), $revenue)) }} L600 200 L0 200 Z" fill="oklch(0.65 0.18 160 / 0.15)" stroke="oklch(0.55 0.18 160)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                        <path d="M{{ implode(' ', array_map(function($i, $v) use ($expenses, $maxChartValue) { $x = ($i + 0.5) * 600 / 12; $y = 200 - ($v / $maxChartValue * 200); return $i === 0 ? 'M' . $x . ' ' . $y : 'L' . $x . ' ' . $y; }, array_keys($expenses), $expenses)) }} L600 200 L0 200 Z" fill="oklch(0.65 0.18 25 / 0.15)" stroke="oklch(0.55 0.18 25)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                    <div class="mt-1 flex justify-between text-[0.6rem] text-slate-400 dark:text-slate-500">
                        @foreach($months as $m)
                            <span>{{ explode(' ', $m)[0] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.attendance_by_class') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Last 30 days') }}</span>
            </div>
            <div class="admin-card-body">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700">
                                <th class="py-2 text-left font-medium text-slate-500 dark:text-slate-400">{{ __('Class') }}</th>
                                <th class="py-2 text-right font-medium text-slate-500 dark:text-slate-400">{{ __('Attendance rate') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse($attendanceByClass as $row)
                                <tr>
                                    <td class="py-2 text-slate-900 dark:text-slate-100">{{ $row->class_name ?: __('Unassigned') }}</td>
                                    <td class="py-2 text-right">
                                        <span class="inline-block h-2 w-16 rounded-full bg-slate-100 align-middle dark:bg-slate-700">
                                            <span class="block h-2 rounded-full {{ $row->rate >= 75 ? 'bg-emerald-500' : ($row->rate >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min(100, $row->rate) }}%"></span>
                                        </span>
                                        <span class="ml-2 align-middle text-slate-600 dark:text-slate-300">{{ $row->rate }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-4 text-center text-slate-500 dark:text-slate-400">{{ __('dashboard.no_attendance_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.teacher_workload') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('dashboard.classes_assigned') }}</span>
            </div>
            <div class="admin-card-body">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700">
                                <th class="py-2 text-left font-medium text-slate-500 dark:text-slate-400">{{ __('Teacher') }}</th>
                                <th class="py-2 text-right font-medium text-slate-500 dark:text-slate-400">{{ __('dashboard.classes_assigned') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse($teacherWorkload as $row)
                                <tr>
                                    <td class="py-2 text-slate-900 dark:text-slate-100">{{ $row->teacher_name }}</td>
                                    <td class="py-2 text-right text-slate-600 dark:text-slate-300">{{ $row->classes_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-4 text-center text-slate-500 dark:text-slate-400">{{ __('dashboard.no_teacher_assignments') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection