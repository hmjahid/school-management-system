@extends('layouts.dashboard')

@section('title', __('Help & Documentation') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Help & Documentation')" :description="__('Welcome to SchoolEase. Use the sections below to learn how to manage your school efficiently.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Help & Documentation')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Getting Started --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-900/40 dark:text-brand-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Getting Started') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('Log in to the admin panel using your credentials.') }}</li>
                <li>&bull; {{ __('Set up your school name, logo, and tagline in School Settings.') }}</li>
                <li>&bull; {{ __('Add classes, then create student and teacher accounts.') }}</li>
                <li>&bull; {{ __('Configure academic sessions and batches before managing attendance or exams.') }}</li>
            </ul>
        </x-card>

        {{-- Managing Students --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Managing Students') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('Navigate to Academic > People > Students to view all students.') }}</li>
                <li>&bull; {{ __('Click "Add Student" to register a new student with class and batch.') }}</li>
                <li>&bull; {{ __('Edit or delete students from the student detail page.') }}</li>
                <li>&bull; {{ __('Use Bulk Import to add many students from a CSV file.') }}</li>
            </ul>
        </x-card>

        {{-- Managing Teachers --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Managing Teachers') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('Go to Academic > People > Teachers to manage faculty.') }}</li>
                <li>&bull; {{ __('Create teacher accounts and assign them to subjects and classes.') }}</li>
                <li>&bull; {{ __('Teachers can be given specific roles for access control.') }}</li>
                <li>&bull; {{ __('Staff directory provides a complete list of all school staff.') }}</li>
            </ul>
        </x-card>

        {{-- Attendance --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Attendance') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('Go to Academic > Daily > Attendance to mark daily attendance.') }}</li>
                <li>&bull; {{ __('Use Bulk Mark to record attendance for an entire class at once.') }}</li>
                <li>&bull; {{ __('Staff attendance tracks teacher and staff presence separately.') }}</li>
                <li>&bull; {{ __('Attendance reports are available under Reports.') }}</li>
            </ul>
        </x-card>

        {{-- Exams & Results --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Exams & Results') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('Create exams under Academic > Academics > Exams.') }}</li>
                <li>&bull; {{ __('Enter marks per subject; results are computed automatically.') }}</li>
                <li>&bull; {{ __('Publish results so students and parents can view them.') }}</li>
                <li>&bull; {{ __('Export results as spreadsheets for offline use.') }}</li>
            </ul>
        </x-card>

        {{-- Fees & Payments --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Fees & Payments') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('Create fee categories and fee types under Academic > Finance.') }}</li>
                <li>&bull; {{ __('Record payments when students submit fees.') }}</li>
                <li>&bull; {{ __('Track expenses and manage the ledger for accounting.') }}</li>
                <li>&bull; {{ __('Generate income statements and balance sheets from Reports.') }}</li>
            </ul>
        </x-card>

        {{-- CMS Management --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('CMS Management') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('Edit static pages (About, Admissions, etc.) under Website CMS.') }}</li>
                <li>&bull; {{ __('Publish news, gallery items, and announcements.') }}</li>
                <li>&bull; {{ __('Manage documents and downloadable files.') }}</li>
                <li>&bull; {{ __('Review form submissions from the public website.') }}</li>
            </ul>
        </x-card>

        {{-- Public Website --}}
        <x-card>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-100 text-cyan-600 dark:bg-cyan-900/40 dark:text-cyan-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Public Website') }}</h2>
            </div>
            <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>&bull; {{ __('The public site is built with Laravel Blade and Tailwind CSS.') }}</li>
                <li>&bull; {{ __('All content is managed from the dashboard CMS section.') }}</li>
                <li>&bull; {{ __('Multi-language support: English and Bengali are built in.') }}</li>
                <li>&bull; {{ __('Use the sitemap at /sitemap.xml for SEO indexing.') }}</li>
            </ul>
        </x-card>
    </div>
@endsection
