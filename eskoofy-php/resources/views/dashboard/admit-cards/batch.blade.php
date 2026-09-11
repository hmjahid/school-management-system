@extends('layouts.dashboard')

@section('title', __('Generate admit cards') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('Generate admit cards')" :description="__('Create admit cards for every student in a class or section for an exam.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Admit cards'), 'url' => route('dashboard.admit-cards.index')],
                ['label' => __('Batch')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="admin-card max-w-2xl">
        <div class="admin-card-header">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Batch options') }}</h2>
        </div>
        <div class="admin-card-body">
            <form method="post" action="{{ route('dashboard.admit-cards.batch.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Exam') }}</label>
                    <select name="exam_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                        <option value="">{{ __('Select exam') }}</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Class') }}</label>
                        <select name="class_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                            <option value="">{{ __('Select class') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Section') }}</label>
                        <select name="section_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                            <option value="">{{ __('All sections') }}</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Issue date') }}</label>
                    <input type="date" name="issue_date" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Header text') }}</label>
                    <input type="text" name="details[header_text]" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Footer text') }}</label>
                    <input type="text" name="details[footer_text]" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Custom notes') }}</label>
                    <textarea name="details[custom_notes]" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"></textarea>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="hidden" name="details[show_logo]" value="0">
                    <input type="checkbox" name="details[show_logo]" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600">
                    {{ __('Show school logo') }}
                </label>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Generate cards') }}
                    </button>
                    <a href="{{ route('dashboard.admit-cards.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection