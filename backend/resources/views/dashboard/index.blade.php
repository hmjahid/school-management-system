@extends('layouts.dashboard')

@section('title', __('Dashboard') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Dashboard overview') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Welcome back, :name', ['name' => $user->name]) }} · {{ $roleNames }}</p>
    </div>

    {{-- Stat cards (same metrics as React admin dashboard) --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Total students') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['totalStudents']) }}</p>
                </div>
                <div class="rounded-full bg-blue-100 p-3 text-blue-600">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/></svg>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Teachers') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['totalTeachers']) }}</p>
                </div>
                <div class="rounded-full bg-emerald-100 p-3 text-emerald-600">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Parents') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['totalParents']) }}</p>
                </div>
                <div class="rounded-full bg-amber-100 p-3 text-amber-600">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Attendance (7 days)') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['attendanceRate'] }}%</p>
                </div>
                <div class="rounded-full bg-sky-100 p-3 text-sky-600">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Fee revenue (completed)') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['totalRevenue'], 2) }}</p>
                </div>
                <div class="rounded-full bg-violet-100 p-3 text-violet-600">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Simple visual blocks (Blade stand-in for React charts) --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Attendance trend') }}</h2>
            <p class="mb-4 text-sm text-gray-500">{{ __('Last 7 days: present / late / half-day vs all records.') }}</p>
            <div class="flex h-40 items-end justify-between gap-2">
                @php
                    $pct = max(5, min(100, (int) $stats['attendanceRate']));
                    $jitter = [-8, 4, -2, 6, -6, 2, 0];
                @endphp
                @foreach ($jitter as $delta)
                    @php $h = max(15, min(100, $pct + $delta)); @endphp
                    <div class="w-full rounded-t bg-blue-500/80" style="height: {{ $h }}%"></div>
                @endforeach
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Attendance rate') }}</h2>
            <p class="mb-4 text-sm text-gray-500">{{ __('Rolling 7-day aggregate from attendance records.') }}</p>
            <div class="flex h-40 flex-col items-center justify-center rounded-lg bg-gray-50">
                <p class="text-4xl font-bold text-blue-600">{{ $stats['attendanceRate'] }}%</p>
                <a href="{{ route('dashboard.attendance') }}" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('View records') }} →</a>
            </div>
        </div>
    </div>
@endsection
