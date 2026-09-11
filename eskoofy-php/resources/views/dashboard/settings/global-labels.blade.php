@extends('layouts.dashboard')

@section('title', __('Global Labels') . ' — ' . config('app.name', 'SchoolEase'))

@php
    $sections = collect($flattened)->groupBy('section');
    $sectionMeta = [
        'nav' => ['icon' => 'M4 6h16M4 12h16M4 18h16', 'label' => __('Navigation')],
        'footer' => ['icon' => 'M17 8l4 4m0 0l-4 4m4-4H3', 'label' => __('Footer')],
        'home' => ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => __('Homepage')],
        'payments' => ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => __('Payments')],
        'portal' => ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => __('Portal')],
        'auth' => ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'label' => __('Authentication')],
        'news' => ['icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 'label' => __('News')],
        'gallery' => ['icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => __('Gallery')],
        'pages' => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => __('Pages')],
        'contact_page' => ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'label' => __('Contact')],
        'admissions_apply' => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => __('Admissions apply')],
        'admissions_landing' => ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => __('Admissions landing')],
        'admission_status' => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'label' => __('Admission status')],
        'admissions_closed' => ['icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636', 'label' => __('Admissions closed')],
        'admissions_bar' => ['icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => __('Admissions bar')],
        'faculty_page' => ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'label' => __('Faculty page')],
        'routine_page' => ['icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'label' => __('Routine page')],
        'certificate_page' => ['icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'label' => __('Certificate page')],
        'admit_card_page' => ['icon' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z', 'label' => __('Admit card page')],
        'student_id_card_page' => ['icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'label' => __('Student ID card page')],
        'assignment_page' => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => __('Assignment page')],
        'portal_progress' => ['icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'label' => __('Portal progress')],
        'payment_status' => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => __('Payment status')],
        'portal_admission' => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => __('Portal admission')],
        'fee_receipt' => ['icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'label' => __('Fee receipt')],
        'news_show' => ['icon' => 'M10 19l-7-7m0 0l7-7m-7 7h18', 'label' => __('News show')],
        'payments_page' => ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => __('Payments page')],
    ];
@endphp

@section('content')
    <x-page-header :title="__('Global Labels')" :description="__('Customize all public-facing text labels. Leave blank to use defaults from language files.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Global Labels')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="post" action="{{ route('dashboard.settings.update.global-labels') }}">
        @csrf
        <div class="space-y-4">
            @foreach ($sections as $section => $fields)
                @continue(!isset($sectionMeta[$section]))
                <details class="group rounded-xl border border-slate-200 bg-white shadow-sm open:ring-1 open:ring-brand-500 dark:border-slate-700 dark:bg-slate-800">
                    <summary class="flex cursor-pointer list-none items-center gap-3 px-5 py-4 [&::-webkit-details-marker]:hidden">
                        <svg class="h-5 w-5 shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sectionMeta[$section]['icon'] }}"/></svg>
                        <span class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $sectionMeta[$section]['label'] }}</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-700 dark:text-slate-400">{{ $fields->count() }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($fields as $field)
                                @php
                                    $enOverride = $field['enOverride'];
                                    if (is_array($enOverride) && isset($field['listDefaults'])) {
                                        $firstKey = array_key_first($field['listDefaults']);
                                        if (is_string($field['listDefaults'][$firstKey] ?? '')) {
                                            $enOverride = implode("\n", $enOverride);
                                        }
                                    }
                                    $enValue = is_array($enOverride) ? '' : $enOverride;
                                    $bnValue = old("labels.bn.{$field['path']}", '');
                                @endphp
                                <div class="{{ $field['isList'] ? 'sm:col-span-2' : '' }} space-y-1.5">
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $field['label'] }}</label>
                                    @if ($field['isList'])
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <textarea name="labels[en][{{ str_replace('.', '][', $field['path']) }}]" rows="3" placeholder="{{ isset($field['listDefaults']) ? implode("\n", $field['listDefaults']) : '' }}" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-brand-500 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:placeholder-slate-500">{{ $enValue }}</textarea>
                                            <textarea name="labels[bn][{{ str_replace('.', '][', $field['path']) }}]" rows="3" placeholder="বাংলা" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-brand-500 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:placeholder-slate-500">{{ $bnValue }}</textarea>
                                        </div>
                                    @else
                                        <input type="text" name="labels[en][{{ str_replace('.', '][', $field['path']) }}]" value="{{ $enValue }}" placeholder="{{ $field['enDefault'] }}" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-brand-500 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:placeholder-slate-500">
                                        <input type="text" name="labels[bn][{{ str_replace('.', '][', $field['path']) }}]" value="{{ $bnValue }}" placeholder="বাংলা" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-brand-500 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:placeholder-slate-500">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <x-button type="submit">{{ __('Save Global Labels') }}</x-button>
        </div>
    </form>
@endsection