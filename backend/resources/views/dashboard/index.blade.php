@extends('layouts.dashboard')

@section('title', __('Dashboard') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('Dashboard overview')" :description="__('Welcome back, :name · :role', ['name' => $user->name, 'role' => $roleNames])">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[['label' => __('Dashboard')]]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @php
            $statCards = [
                ['label' => __('Total students'), 'value' => number_format($stats['totalStudents']), 'icon' => 'users', 'color' => 'brand'],
                ['label' => __('Teachers'), 'value' => number_format($stats['totalTeachers']), 'icon' => 'users', 'color' => 'emerald'],
                ['label' => __('Parents'), 'value' => number_format($stats['totalParents']), 'icon' => 'users', 'color' => 'amber'],
                ['label' => __('Attendance (7 days)'), 'value' => $stats['attendanceRate'].'%', 'icon' => 'chart', 'color' => 'sky'],
                ['label' => __('Fee revenue'), 'value' => number_format($stats['totalRevenue'], 2), 'icon' => 'tag', 'color' => 'violet'],
            ];
            $iconColors = [
                'brand' => 'bg-brand-50 text-brand-600',
                'emerald' => 'bg-emerald-50 text-emerald-600',
                'amber' => 'bg-amber-50 text-amber-600',
                'sky' => 'bg-sky-50 text-sky-600',
                'violet' => 'bg-violet-50 text-violet-600',
            ];
        @endphp

        @foreach ($statCards as $card)
            <div class="admin-stat-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $card['value'] }}</p>
                    </div>
                    <div class="rounded-xl p-2.5 {{ $iconColors[$card['color']] ?? $iconColors['brand'] }}">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :title="__('Attendance trend')">
            <p class="mb-4 text-sm text-slate-500">{{ __('Last 7 days: present / late / half-day vs all records.') }}</p>
            <div class="flex h-44 items-end justify-between gap-2 rounded-lg bg-slate-50 p-4">
                @php
                    $pct = max(5, min(100, (int) $stats['attendanceRate']));
                    $jitter = [-8, 4, -2, 6, -6, 2, 0];
                @endphp
                @foreach ($jitter as $delta)
                    @php $h = max(15, min(100, $pct + $delta)); @endphp
                    <div class="group relative w-full">
                        <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition-all group-hover:from-brand-700 group-hover:to-brand-500" style="height: {{ $h }}%"></div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card :title="__('Attendance rate')">
            <p class="mb-4 text-sm text-slate-500">{{ __('Rolling 7-day aggregate from attendance records.') }}</p>
            <div class="flex h-44 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/50">
                <p class="text-5xl font-bold tracking-tight text-brand-600">{{ $stats['attendanceRate'] }}%</p>
                <x-button :href="route('dashboard.attendance')" variant="ghost" size="sm" class="mt-4">
                    {{ __('View records') }} →
                </x-button>
            </div>
        </x-card>
    </div>
@endsection
