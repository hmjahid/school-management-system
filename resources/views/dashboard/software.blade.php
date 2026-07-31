@extends('layouts.dashboard')

@section('title', __('About') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        {{-- Header --}}
        <div class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 p-8 text-white shadow-lg">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">{{ config('app.name', 'SchoolEase') }}</h1>
                    <p class="mt-1 text-white/80">School Management System</p>
                </div>
            </div>
        </div>

        {{-- Software Details --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Software Details') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Software Name') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ config('app.name', 'SchoolEase') }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Version') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">1.0.0</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Framework') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">Laravel {{ app()->version() }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('PHP Version') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ phpversion() }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Database') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ config('database.default') }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('License') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">Proprietary</p>
                </div>
            </div>
        </div>

        {{-- Key Features --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Key Features') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'label' => 'Student Management'],
                        ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label' => 'Attendance Tracking'],
                        ['icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'label' => 'Exam & Results'],
                        ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Fee Collection'],
                        ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Class Routine'],
                        ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Transport & Hostel'],
                        ['icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z', 'label' => 'Notice Board'],
                        ['icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Gallery & Media'],
                        ['icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9', 'label' => 'Multi-language (EN/BN)'],
                    ];
                @endphp
                @foreach($features as $f)
                    <div class="flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                        <svg class="h-5 w-5 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"/></svg>
                        <span class="text-sm font-medium text-gray-700">{{ $f['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Developer Info --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Developer Information') }}</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Developed By') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">SchoolEase Development Team</p>
                    </div>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Support Email') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">support@schoolease.dev</p>
                    </div>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Website') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">https://schoolease.dev</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- System Info --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('System Information') }}</h2>
            <div class="overflow-hidden rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-gray-50"><td class="px-4 py-2 font-medium text-gray-700">{{ __('Server') }}</td><td class="px-4 py-2 text-gray-600">{{ php_uname('s') . ' ' . php_uname('r') }}</td></tr>
                        <tr><td class="px-4 py-2 font-medium text-gray-700">{{ __('PHP Version') }}</td><td class="px-4 py-2 text-gray-600">{{ phpversion() }}</td></tr>
                        <tr class="bg-gray-50"><td class="px-4 py-2 font-medium text-gray-700">{{ __('Laravel Version') }}</td><td class="px-4 py-2 text-gray-600">{{ app()->version() }}</td></tr>
                        <tr><td class="px-4 py-2 font-medium text-gray-700">{{ __('Database') }}</td><td class="px-4 py-2 text-gray-600">{{ config('database.default') }}</td></tr>
                        <tr class="bg-gray-50"><td class="px-4 py-2 font-medium text-gray-700">{{ __('Cache Driver') }}</td><td class="px-4 py-2 text-gray-600">{{ config('cache.default') }}</td></tr>
                        <tr><td class="px-4 py-2 font-medium text-gray-700">{{ __('Queue Driver') }}</td><td class="px-4 py-2 text-gray-600">{{ config('queue.default') }}</td></tr>
                        <tr class="bg-gray-50"><td class="px-4 py-2 font-medium text-gray-700">{{ __('Mail Driver') }}</td><td class="px-4 py-2 text-gray-600">{{ config('mail.default') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Open Source Credits --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Credits & Dependencies') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @php
                    $deps = [
                        'Laravel Framework' => 'The PHP Framework for Web Artisans',
                        'Spatie Laravel Permission' => 'Role & permission management',
                        'Spatie Laravel Activitylog' => 'Activity logging',
                        'Tailwind CSS' => 'Utility-first CSS framework',
                        'Vite' => 'Next generation frontend tooling',
                        'Alpine.js' => 'Sprightly JavaScript framework',
                    ];
                @endphp
                @foreach($deps as $name => $desc)
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-sm font-semibold text-gray-900">{{ $name }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
