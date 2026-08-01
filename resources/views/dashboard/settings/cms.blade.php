@extends('layouts.dashboard')

@section('title', __('CMS settings') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('CMS settings')" :description="__('Toggle visibility of homepage sections and sub-pages.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('CMS settings')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="post" action="{{ route('dashboard.settings.update.cms') }}" class="max-w-3xl">
        @csrf
        <x-card :title="__('Homepage sections')" class="mb-6">
            <p class="mb-5 text-sm text-slate-500">{{ __('Toggle visibility of each section on the public homepage.') }}</p>

            @php
                $vis = $settings->section_visibility ?? [
                    'hero' => true, 'features' => true, 'stats' => true, 'principal' => true,
                    'teachers' => true, 'committee_members' => true, 'testimonials' => true, 'remarkable_students' => true,
                    'slider' => true, 'events' => true, 'news' => true, 'highlights' => true,
                    'cta' => true, 'partners' => true, 'admissions_bar' => true, 'urgent_notices' => true,
                ];
                $sectionLabels = [
                    'hero' => 'Hero banner',
                    'features' => 'Features',
                    'stats' => 'Stats counter',
                    'principal' => "Principal's message",
                    'teachers' => 'Teachers section',
                    'committee_members' => 'Managing Committee',
                    'testimonials' => 'Testimonials',
                    'remarkable_students' => 'Remarkable students',
                    'slider' => 'Photo slider',
                    'events' => 'Upcoming events',
                    'news' => 'Latest news',
                    'highlights' => 'Highlights',
                    'cta' => 'CTA banner',
                    'partners' => 'Partners strip',
                    'admissions_bar' => 'Admissions top bar',
                    'urgent_notices' => 'Urgent notices (hero)',
                ];
            @endphp
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($sectionLabels as $key => $label)
                    <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
                        <input type="hidden" name="section_visibility[{{ $key }}]" value="0">
                        <input type="checkbox" name="section_visibility[{{ $key }}]" value="1"
                            @checked(old("section_visibility.{$key}", $vis[$key] ?? true))
                            class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            @php
                $otherPageSections = [
                    'Admissions' => [
                        'adm_hero' => 'Hero banner', 'adm_process' => 'Admission process', 'adm_fee' => 'Fee structure',
                        'adm_prospectus' => 'Download prospectus', 'adm_faq' => 'FAQs', 'adm_cta' => 'CTA banner',
                        'adm_scholarship' => 'Scholarship form',
                    ],
                    'Contact' => [
                        'contact_hero' => 'Hero banner', 'contact_cards' => 'Contact cards', 'contact_form' => 'Contact form',
                        'contact_hours' => 'Opening hours', 'contact_emergency' => 'Emergency contacts',
                        'contact_map' => 'Map', 'contact_faq' => 'FAQs',
                    ],
                    'Faculty' => ['faculty_hero' => 'Hero banner', 'faculty_search' => 'Search & filter', 'faculty_grid' => 'Faculty grid'],
                    'Gallery' => ['gallery_hero' => 'Hero banner', 'gallery_tabs' => 'Category tabs', 'gallery_grid' => 'Gallery grid'],
                    'News' => ['news_hero' => 'Hero banner', 'news_featured' => 'Featured article', 'news_grid' => 'News grid'],
                    'Payments' => ['payments_hero' => 'Hero banner', 'payments_fee' => 'Fee table', 'payments_gateways' => 'Payment gateways'],
                    'About / Pages' => ['page_hero' => 'Hero banner', 'page_content' => 'Page content'],
                    'Events' => ['events_hero' => 'Hero banner', 'events_filters' => 'Filters & view toggle', 'events_upcoming' => 'Upcoming events', 'events_past' => 'Past events'],
                    'Notices' => ['notices_hero' => 'Hero banner', 'notices_list' => 'Notices list'],
                    'Results' => ['results_hero' => 'Hero banner', 'results_form' => 'Search form'],
                    'Routines' => ['routines_hero' => 'Hero banner', 'routines_filter' => 'Filter form', 'routines_grid' => 'Routine grid'],
                    'Transport' => ['transport_hero' => 'Hero banner', 'transport_routes' => 'Route cards', 'transport_fleet' => 'Fleet section', 'transport_map' => 'Route map'],
                ];
            @endphp
            <div class="mt-6 space-y-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Other page sections') }}</h3>
                @foreach($otherPageSections as $pageTitle => $sections)
                    <div>
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $pageTitle }}</h4>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($sections as $key => $label)
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
                                    <input type="hidden" name="section_visibility[{{ $key }}]" value="0">
                                    <input type="checkbox" name="section_visibility[{{ $key }}]" value="1"
                                        @checked(old("section_visibility.{$key}", $vis[$key] ?? true))
                                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button type="submit">{{ __('Save settings') }}</x-button>
        </div>
    </form>
@endsection
