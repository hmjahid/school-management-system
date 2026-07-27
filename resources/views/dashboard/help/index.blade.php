@extends('layouts.dashboard')

@section('title', __('Help & Documentation') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Help & Documentation')" :description="__('help.page_description')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Help & Documentation')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    {{-- Search bar --}}
    <div class="mb-8" data-help-search-wrap>
        <div class="relative max-w-xl">
            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" data-help-search placeholder="{{ __('help.search_placeholder') }}"
                class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500">
        </div>
    </div>

    {{-- Quick-nav pills --}}
    <div class="mb-8 flex flex-wrap gap-2" data-help-nav>
        @foreach(__('help.sections') as $key => $section)
            <a href="#help-{{ $key }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-blue-400" data-help-nav-item data-help-nav-key="{{ $key }}">
                {{ $section['title'] }}
            </a>
        @endforeach
    </div>

    {{-- No results message --}}
    <div class="hidden rounded-xl border border-amber-200 bg-amber-50 p-6 text-center text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400" data-help-no-results>
        {{ __('help.no_results') }}
    </div>

    {{-- Accordion sections --}}
    <div class="space-y-4" data-help-accordion>
        @foreach(__('help.sections') as $key => $section)
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition dark:border-slate-700 dark:bg-slate-800" data-help-section data-help-section-key="{{ $key }}" id="help-{{ $key }}">
                <button type="button" class="flex w-full items-center gap-4 px-6 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-slate-700/50" data-help-toggle>
                    @php
                        $colors = [
                            'getting_started' => ['bg' => 'bg-brand-100 dark:bg-brand-900/40', 'text' => 'text-brand-600 dark:text-brand-400', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
                            'managing_students' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/40', 'text' => 'text-emerald-600 dark:text-emerald-400', 'icon' => '<path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>'],
                            'managing_teachers' => ['bg' => 'bg-blue-100 dark:bg-blue-900/40', 'text' => 'text-blue-600 dark:text-blue-400', 'icon' => '<path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>'],
                            'attendance' => ['bg' => 'bg-amber-100 dark:bg-amber-900/40', 'text' => 'text-amber-600 dark:text-amber-400', 'icon' => '<path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>'],
                            'exams_results' => ['bg' => 'bg-violet-100 dark:bg-violet-900/40', 'text' => 'text-violet-600 dark:text-violet-400', 'icon' => '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>'],
                            'fees_payments' => ['bg' => 'bg-teal-100 dark:bg-teal-900/40', 'text' => 'text-teal-600 dark:text-teal-400', 'icon' => '<path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>'],
                            'cms_management' => ['bg' => 'bg-rose-100 dark:bg-rose-900/40', 'text' => 'text-rose-600 dark:text-rose-400', 'icon' => '<path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/>'],
                            'public_website' => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/40', 'text' => 'text-cyan-600 dark:text-cyan-400', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>'],
                        ];
                        $c = $colors[$key] ?? ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-400', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'];
                    @endphp
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $c['icon'] !!}</svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $section['title'] }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400 truncate">{{ $section['description'] }}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" data-help-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="hidden border-t border-slate-100 px-6 py-5 dark:border-slate-700" data-help-content>
                    <ol class="space-y-3">
                        @foreach($section['steps'] as $i => $step)
                            <li class="flex items-start gap-3" data-help-step>
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">{{ $i + 1 }}</span>
                                <span class="pt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Was this helpful --}}
                    <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('help.was_this_helpful') }}</span>
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400" data-help-feedback="yes">{{ __('help.yes') }}</button>
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:bg-red-50 hover:text-red-600 hover:border-red-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-red-900/20 dark:hover:text-red-400" data-help-feedback="no">{{ __('help.no') }}</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
(function(){
    var sections = document.querySelectorAll('[data-help-section]');
    var searchInput = document.querySelector('[data-help-search]');
    var noResults = document.querySelector('[data-help-no-results]');

    // Accordion toggle
    sections.forEach(function(section){
        var btn = section.querySelector('[data-help-toggle]');
        var content = section.querySelector('[data-help-content]');
        var chevron = section.querySelector('[data-help-chevron]');
        btn.addEventListener('click', function(){
            var isOpen = !content.classList.contains('hidden');
            // Close all others
            sections.forEach(function(s){
                s.querySelector('[data-help-content]').classList.add('hidden');
                var ch = s.querySelector('[data-help-chevron]');
                if (ch) ch.style.transform = '';
            });
            if (!isOpen) {
                content.classList.remove('hidden');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
            }
        });
    });

    // Quick nav click
    document.querySelectorAll('[data-help-nav-item]').forEach(function(link){
        link.addEventListener('click', function(e){
            e.preventDefault();
            var key = this.dataset.helpNavKey;
            var target = document.getElementById('help-' + key);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Open the section
                var content = target.querySelector('[data-help-content]');
                var chevron = target.querySelector('[data-help-chevron]');
                if (content && content.classList.contains('hidden')) {
                    // Close others first
                    sections.forEach(function(s){
                        s.querySelector('[data-help-content]').classList.add('hidden');
                        var ch = s.querySelector('[data-help-chevron]');
                        if (ch) ch.style.transform = '';
                    });
                    content.classList.remove('hidden');
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                }
            }
        });
    });

    // Search
    if (searchInput) {
        searchInput.addEventListener('input', function(){
            var q = this.value.toLowerCase().trim();
            var visibleCount = 0;
            sections.forEach(function(section){
                var title = (section.querySelector('h2')?.textContent || '').toLowerCase();
                var desc = (section.querySelector('p')?.textContent || '').toLowerCase();
                var steps = Array.from(section.querySelectorAll('[data-help-step]')).map(function(s){ return s.textContent.toLowerCase(); }).join(' ');
                var match = !q || title.includes(q) || desc.includes(q) || steps.includes(q);
                section.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            if (noResults) {
                noResults.classList.toggle('hidden', visibleCount > 0);
            }
        });
    }

    // Feedback buttons (visual only)
    document.querySelectorAll('[data-help-feedback]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var wrap = this.closest('div');
            wrap.innerHTML = '<span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Thank you for your feedback!</span>';
        });
    });
})();
</script>
@endpush
