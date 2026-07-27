@extends('layouts.app')

@section('title', ($content->title ?? site_ui('nav.faculty')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @if($siteSettings->section_visibility['faculty_hero'] ?? true)
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold md:text-5xl">{{ $content->title ?? site_ui('nav.faculty') }}</h1>
                @if($content->meta_description ?? false)
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ $content->meta_description }}</p>
                @endif
            </div>
        </div>
        @endif

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            @if($siteSettings->section_visibility['faculty_search'] ?? true)
            {{-- Search and filter --}}
            <div class="mb-10 flex flex-col gap-4 sm:flex-row reveal">
                <div class="relative flex-1">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" data-faculty-search placeholder="{{ __('Search teachers by name, designation, or subject...') }}"
                        class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-sm">
                </div>
                <select data-faculty-department class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-sm">
                    <option value="all">{{ __('All Departments') }}</option>
                    <option value="science">{{ __('Science') }}</option>
                    <option value="arts">{{ __('Arts') }}</option>
                    <option value="commerce">{{ __('Commerce') }}</option>
                    <option value="sports">{{ __('Sports') }}</option>
                </select>
            </div>
            @endif

            @if($siteSettings->section_visibility['faculty_grid'] ?? true)
            {{-- Faculty grid --}}
            @if($teachers->isEmpty())
                <div class="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="mt-4 text-sm text-slate-500">{{ site_ui('faculty_page.empty') }}</p>
                </div>
            @else
                @php $visibleCount = 6; @endphp
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-faculty-grid>
                    @foreach ($teachers as $index => $teacher)
                        @php
                            $name = $teacher->user?->name ?? site_ui('faculty_page.staff_fallback');
                            $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
                        @endphp
                        <div class="group rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl reveal {{ $index >= $visibleCount ? 'hidden' : '' }}" data-faculty-card {{ $index >= $visibleCount ? 'data-faculty-extra' : '' }}
                            data-name="{{ strtolower($name) }}"
                            data-department="{{ strtolower($teacher->department ?? 'general') }}"
                            data-subjects="{{ strtolower($teacher->subjects ?? '') }}">
                            <div class="flex flex-col items-center text-center">
                                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 ring-4 ring-white shadow-lg transition-transform duration-300 group-hover:scale-105">
                                    {{ $initials }}
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $name }}</h3>
                                <p class="text-sm text-slate-500">{{ $teacher->designation ?? __('Teacher') }}</p>
                                @if($teacher->qualification)
                                    <p class="mt-1 text-xs text-slate-400">{{ $teacher->qualification }}</p>
                                @endif
                            </div>

                            @if($teacher->subjects)
                                <div class="mt-4 flex flex-wrap justify-center gap-1.5">
                                    @foreach(explode(',', $teacher->subjects) as $subj)
                                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ trim($subj) }}</span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Expand for details --}}
                            <details class="group mt-4 border-t border-slate-100 pt-4">
                                <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-blue-600 transition-colors">
                                    <span>{{ __('View Details') }}</span>
                                    <svg class="h-4 w-4 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </summary>
                                <div class="mt-3 space-y-2 text-sm text-slate-600">
                                    @if($teacher->phone)
                                        <p class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {{ $teacher->phone }}
                                        </p>
                                    @endif
                                    @if($teacher->email)
                                        <p class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {{ $teacher->email }}
                                        </p>
                                    @endif
                                    @if($teacher->bio)
                                        <p class="text-sm text-slate-500 italic">"{{ \Illuminate\Support\Str::limit($teacher->bio, 150) }}"</p>
                                    @endif
                                </div>
                            </details>
                        </div>
                    @endforeach
                </div>

                @if($teachers->count() > $visibleCount)
                    <div class="mt-10 text-center" data-faculty-toggle-wrap>
                        <p class="mb-4 text-sm text-slate-500" data-faculty-count>{{ site_ui('faculty_page.showing_of', ['shown' => $visibleCount, 'total' => $teachers->count()]) }}</p>
                        <button type="button" data-faculty-toggle class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 hover:shadow-md">
                            <span data-faculty-toggle-text>{{ site_ui('faculty_page.see_more') }}</span>
                            <svg data-faculty-toggle-icon class="h-4 w-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                @endif
            @endif
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    (function(){
        var btn = document.querySelector('[data-faculty-toggle]');
        var extras = document.querySelectorAll('[data-faculty-extra]');
        var textEl = document.querySelector('[data-faculty-toggle-text]');
        var iconEl = document.querySelector('[data-faculty-toggle-icon]');
        var countEl = document.querySelector('[data-faculty-count]');
        if (!btn || extras.length === 0) return;

        var expanded = false;
        var total = document.querySelectorAll('[data-faculty-card]').length;
        var visibleCount = 6;

        btn.addEventListener('click', function(){
            expanded = !expanded;
            extras.forEach(function(card){
                if (expanded) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
            textEl.textContent = expanded
                ? '{{ site_ui("faculty_page.see_less") }}'
                : '{{ site_ui("faculty_page.see_more") }}';
            iconEl.style.transform = expanded ? 'rotate(180deg)' : '';
            if (countEl) {
                countEl.textContent = expanded
                    ? '{{ site_ui("faculty_page.showing_of", ["shown" => ":total", "total" => ":total"]) }}'.replace(':total', total).replace(':total', total)
                    : '{{ site_ui("faculty_page.showing_of", ["shown" => "6", "total" => ":total"]) }}'.replace(':total', total);
            }
        });

        var searchInput = document.querySelector('[data-faculty-search]');
        if (searchInput) {
            searchInput.addEventListener('input', function(){
                var q = this.value.toLowerCase().trim();
                document.querySelectorAll('[data-faculty-card]').forEach(function(card){
                    var name = card.dataset.name || '';
                    var dept = card.dataset.department || '';
                    var subjects = card.dataset.subjects || '';
                    var match = !q || name.includes(q) || dept.includes(q) || subjects.includes(q);
                    card.style.display = match ? '' : 'none';
                });
            });
        }
    })();
    </script>
    @endpush
@endsection
