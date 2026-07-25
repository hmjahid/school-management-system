@extends('layouts.app')

@section('title', ($homeContent->title ?? site_ui('nav.home')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $homeContent->meta_description ?? $siteSettings?->meta_description)

@section('content')
    @php
        $hc = is_array($homeContent->content ?? null) ? $homeContent->content : [];
        $hero = $hc['hero'] ?? [];
        $highlights = $hc['highlights'] ?? [];
        $principal = $hc['principal'] ?? [];
        $testimonials = $hc['testimonials'] ?? [];
        $features = $hc['features'] ?? [];
        if (empty($features)) {
            $features = site_ui('home.features_default', []);
        }
        $quickLinks = $hc['quick_links'] ?? null;
        if (! is_array($quickLinks) || empty($quickLinks)) {
            $quickLinks = site_ui('home.quick_links', []);
        }
        $heroImg = $hero['background_image'] ?? null;
        $sectionVis = $siteSettings->section_visibility ?? [];
        if (! $heroImg) {
            $heroImg = 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1920&q=80';
        }
        $headline = $hero['headline'] ?? site_ui('home.hero_headline');
        $sub = $hero['motto'] ?? $hero['subtitle'] ?? site_ui('home.hero_subtitle');
        $principalMessage = $principal['message'] ?? site_ui('home.principal_message_default');
        $testimonialsFallback = $testimonials ?: site_ui('home.testimonials_default', []);
        $highlightsFallback = $highlights ?: site_ui('home.highlights_default', []);
    @endphp

    {{-- Hero Section --}}
    @if($sectionVis['hero'] ?? true)
    <section class="relative min-h-[85vh] flex items-center overflow-hidden bg-gray-900">
        <div class="absolute inset-0">
            <img src="{{ $heroImg }}" alt="" class="h-full w-full object-cover" loading="eager" width="1920" height="1080">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/80 to-gray-900/40"></div>
        </div>

        <div class="relative z-10 mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-12 lg:items-start">

                {{-- Left: School identity + headline --}}
                <div class="lg:col-span-7 xl:col-span-7">
                    @if($siteSettings && $siteSettings->localized_school_name)
                        <div class="mb-6 inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-5 py-2.5 backdrop-blur-sm">
                            @if($siteSettings->logo_url)
                                <img src="{{ $siteSettings->logo_url }}" alt="" class="h-8 w-8 rounded-full object-cover ring-2 ring-white/20">
                            @else
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-sm font-bold text-white">{{ strtoupper(mb_substr($siteSettings->localized_school_name, 0, 1)) }}</span>
                            @endif
                            <span class="text-sm font-semibold uppercase tracking-widest text-orange-300">{{ $siteSettings->localized_school_name }}</span>
                        </div>
                    @endif

                    <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl xl:text-7xl">
                        {{ $headline }}
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg leading-relaxed text-gray-300 sm:text-xl">{{ $sub }}</p>

                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/25 transition-all duration-300 hover:bg-orange-600 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-0.5">
                            {{ site_ui('home.hero_cta_primary') }}
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="{{ route('site.about') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/5 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/10 hover:border-white/40">
                            {{ site_ui('home.hero_cta_secondary') }}
                        </a>
                    </div>

                    {{-- Quick stats row --}}
                    <div class="mt-14 grid grid-cols-2 gap-6 sm:grid-cols-4 sm:gap-8">
                        @php
                            $heroStats = [
                                ['value' => $stats['students'] ?? 0, 'label' => site_ui('home.stats_students'), 'suffix' => '+'],
                                ['value' => $stats['teachers'] ?? 0, 'label' => site_ui('home.stats_faculty'), 'suffix' => '+'],
                                ['value' => $stats['years'] ?? 0, 'label' => site_ui('home.stats_years'), 'suffix' => '+'],
                                ['value' => $stats['awards'] ?? 0, 'label' => site_ui('home.stats_awards') ?? __('Awards'), 'suffix' => '+'],
                            ];
                        @endphp
                        @foreach($heroStats as $i => $stat)
                            <div class="@if(!$loop->first) sm:border-l sm:border-white/10 sm:pl-8 @endif">
                                <div class="text-2xl font-bold text-white sm:text-3xl" data-countup data-target="{{ $stat['value'] }}" data-suffix="{{ $stat['suffix'] }}">0</div>
                                <div class="mt-1 text-xs font-medium uppercase tracking-wider text-gray-400 sm:text-sm">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Right: Notice panel --}}
                @if(($sectionVis['urgent_notices'] ?? true) && $recentNotices->isNotEmpty())
                    <div class="lg:col-span-5 xl:col-span-5">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] backdrop-blur-md">
                            <div class="flex items-center justify-between px-6 pt-5 pb-4 sm:px-7">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500/20">
                                        <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <h3 class="text-base font-bold text-white">{{ __('Latest Notices') }}</h3>
                                </div>
                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-gray-300">{{ $recentNotices->count() }} {{ __('new') }}</span>
                            </div>

                            {{-- Auto-scrolling notice list (bottom→top, ~4 visible) --}}
                            @php
                                $visibleCount = 4;
                                $noticeHeight = 88;
                                $gap = 10;
                                $visibleHeight = ($visibleCount * $noticeHeight) + (($visibleCount - 1) * $gap);
                                $totalNotices = $recentNotices->count();
                                $scrollDuration = max(8, $totalNotices * 3);
                            @endphp
                            <div class="relative px-6 pb-5 sm:px-7">
                                {{-- Gradient masks --}}
                                <div class="pointer-events-none absolute inset-x-6 sm:inset-x-7 top-0 h-6 bg-gradient-to-b from-white/[0.07] to-transparent z-10 sm:inset-x-7"></div>
                                <div class="pointer-events-none absolute inset-x-6 sm:inset-x-7 bottom-5 h-6 bg-gradient-to-t from-white/[0.07] to-transparent z-10 sm:inset-x-7"></div>

                                <div
                                    class="notice-scroll-container overflow-hidden"
                                    style="height: {{ $visibleHeight }}px;"
                                    data-scroll-speed="{{ $scrollDuration }}"
                                >
                                    <div class="notice-scroll-content">
                                        {{-- Original set --}}
                                        @foreach($recentNotices as $notice)
                                            <div class="notice-item rounded-xl border border-white/[0.06] bg-white/[0.04] p-4 transition-all duration-200 hover:border-white/[0.12] hover:bg-white/[0.08]" style="min-height: {{ $noticeHeight }}px;">
                                                <div class="flex items-start gap-3">
                                                    @if($notice->is_urgent)
                                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-red-500 shadow-sm shadow-red-500/50 animate-pulse"></span>
                                                    @elseif($notice->pinned)
                                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>
                                                    @else
                                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-400/70"></span>
                                                    @endif
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <h4 class="text-sm font-semibold text-white leading-snug">{{ $notice->title }}</h4>
                                                            @if($notice->is_urgent)
                                                                <span class="shrink-0 rounded bg-red-500/20 px-1.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-wider text-red-300">{{ __('Urgent') }}</span>
                                                            @endif
                                                        </div>
                                                        <p class="mt-1 text-xs leading-relaxed text-gray-400 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($notice->content), 100) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        {{-- Duplicate for seamless loop --}}
                                        @foreach($recentNotices as $notice)
                                            <div class="notice-item rounded-xl border border-white/[0.06] bg-white/[0.04] p-4 transition-all duration-200 hover:border-white/[0.12] hover:bg-white/[0.08]" style="min-height: {{ $noticeHeight }}px;">
                                                <div class="flex items-start gap-3">
                                                    @if($notice->is_urgent)
                                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-red-500 shadow-sm shadow-red-500/50 animate-pulse"></span>
                                                    @elseif($notice->pinned)
                                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>
                                                    @else
                                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-400/70"></span>
                                                    @endif
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <h4 class="text-sm font-semibold text-white leading-snug">{{ $notice->title }}</h4>
                                                            @if($notice->is_urgent)
                                                                <span class="shrink-0 rounded bg-red-500/20 px-1.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-wider text-red-300">{{ __('Urgent') }}</span>
                                                            @endif
                                                        </div>
                                                        <p class="mt-1 text-xs leading-relaxed text-gray-400 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($notice->content), 100) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </section>
    @endif

    {{-- Features Section --}}
    @if($sectionVis['features'] ?? true)
    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center reveal">
                <h2 class="mb-4 text-4xl font-bold text-gray-900">{{ site_ui('home.features_title') }}</h2>
                <div class="mx-auto h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{{ site_ui('home.features_intro') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $index => $feature)
                    <div class="group rounded-2xl bg-white p-8 shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:ring-blue-100 reveal">
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 text-2xl text-blue-600 font-bold group-hover:from-blue-100 group-hover:to-indigo-100 transition-colors">{{ $index + 1 }}</div>
                        <h3 class="mb-3 text-center text-xl font-semibold text-gray-900">{{ $feature['title'] ?? '' }}</h3>
                        <p class="text-center text-gray-600 leading-relaxed">{{ $feature['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- About/Stats Section --}}
    @if($sectionVis['stats'] ?? true)
    <section class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 py-20 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4 text-center reveal">
                <div class="p-6 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10">
                    <div class="text-4xl font-bold" data-countup data-target="{{ $stats['students'] ?? 0 }}" data-suffix="+">0</div>
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ site_ui('home.stats_students') }}</div>
                </div>
                <div class="p-6 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10">
                    <div class="text-4xl font-bold" data-countup data-target="{{ $stats['teachers'] ?? 0 }}" data-suffix="+">0</div>
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ site_ui('home.stats_faculty') }}</div>
                </div>
                <div class="p-6 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10">
                    <div class="text-4xl font-bold" data-countup data-target="{{ $stats['years'] ?? 0 }}" data-suffix="+">0</div>
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ site_ui('home.stats_years') }}</div>
                </div>
                <div class="p-6 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10">
                    <div class="text-4xl font-bold" data-countup data-target="{{ $stats['awards'] ?? 0 }}" data-suffix="+">0</div>
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ __('Awards') }}</div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Principal's Message --}}
    @if(($sectionVis['principal'] ?? true) && !empty($principalMessage))
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2 reveal">
                    <div class="relative">
                        @if(!empty($principal['photo']))
                            <img src="{{ $principal['photo'] }}" alt="" class="w-full rounded-2xl shadow-2xl object-cover aspect-[4/5]">
                        @else
                            <div class="w-full aspect-[4/5] rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                                <svg class="h-24 w-24 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                            </div>
                        @endif
                        <div class="absolute -bottom-4 -right-4 h-24 w-24 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-xl">
                            <svg class="h-10 w-10 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-4xl font-bold text-gray-900">{{ site_ui('home.principal_title') }}</h2>
                        <div class="mt-3 h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                        <blockquote class="mt-8 text-lg leading-relaxed text-gray-600 italic border-l-4 border-orange-400 pl-6">
                            {{ $principalMessage }}
                        </blockquote>
                        @if(!empty($principal['name']))
                            <div class="mt-8 flex items-center gap-4">
                                <div class="h-1 w-8 bg-orange-400 rounded-full"></div>
                                <div>
                                    <p class="text-xl font-bold text-gray-900">{{ $principal['name'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $principal['designation'] ?? __('Principal') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if(($sectionVis['testimonials'] ?? true) && count($testimonialsFallback))
        <section class="bg-slate-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-14 text-center reveal">
                    <h2 class="mb-4 text-4xl font-bold text-gray-900">{{ site_ui('home.testimonials_title') }}</h2>
                    <div class="mx-auto h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                </div>
                <div class="mx-auto grid max-w-5xl gap-8 md:grid-cols-2">
                    @foreach ($testimonialsFallback as $t)
                        <div class="relative rounded-2xl bg-white p-8 shadow-md ring-1 ring-gray-100 reveal">
                            <svg class="absolute top-6 left-6 h-10 w-10 text-orange-200" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.544 6.068 5.982 8.79 5.982 11H10v10H0z"/></svg>
                            <p class="relative z-10 mb-6 mt-4 text-lg text-gray-700 leading-relaxed">{{ $t['quote'] ?? '' }}</p>
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-lg font-bold text-blue-600">
                                    {{ \Illuminate\Support\Str::substr($t['name'] ?? 'A', 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $t['name'] ?? '' }}</h4>
                                    <p class="text-sm text-gray-500">{{ $t['role'] ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Upcoming Events --}}
    @if(($sectionVis['events'] ?? true) && $upcomingEvents->isNotEmpty())
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-4 reveal">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">{{ site_ui('home.events_title') }}</h2>
                        <div class="mt-2 h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                    </div>
                    <a href="{{ route('site.news') }}" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">{{ site_ui('home.events_view_all') }} <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
                </div>
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ($upcomingEvents->take(6) as $ev)
                        <div class="group rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl reveal">
                            <div class="inline-flex items-center gap-2 rounded-lg bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <time datetime="{{ $ev->start_date?->toIso8601String() }}">{{ $ev->start_date?->format('M j, Y') }}</time>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $ev->title }}</h3>
                            @if($ev->location)
                                <p class="mt-2 flex items-center gap-1.5 text-sm text-gray-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $ev->location }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Latest News --}}
    @if(($sectionVis['news'] ?? true) && $latestNews->isNotEmpty())
        <section class="bg-slate-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-4 reveal">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">{{ site_ui('home.news_title') }}</h2>
                        <div class="mt-2 h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                    </div>
                    <a href="{{ route('site.news') }}" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">{{ site_ui('home.news_view_all') }} <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestNews->take(6) as $item)
                        <div class="group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl reveal">
                            <div class="h-52 overflow-hidden bg-slate-200">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                                        <svg class="h-12 w-12 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                    </div>
                                @endif
                                <div class="absolute top-4 left-4">
                                    <span class="rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">{{ $item->category ?? __('News') }}</span>
                                </div>
                            </div>
                            <div class="p-6">
                                @if($item->published_at)
                                    <div class="mb-2 text-sm text-slate-500">{{ $item->published_at->format('M j, Y') }}</div>
                                @endif
                                <h3 class="mb-3 text-xl font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $item->title }}</h3>
                                <p class="mb-4 text-slate-600 leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($item->content), 140) }}</p>
                                <a href="{{ route('site.news.show', $item->slug) }}" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">
                                    {{ site_ui('home.read_more') }}
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Highlights --}}
    @if(($sectionVis['highlights'] ?? true) && count($highlightsFallback))
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-4 text-center text-2xl font-bold text-gray-900 reveal">{{ site_ui('home.highlights_title') }}</h2>
                <div class="mx-auto mb-8 h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 reveal">
                    @foreach ($highlightsFallback as $h)
                        <li class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-gray-700 transition-colors hover:bg-blue-50 hover:border-blue-200">
                            <svg class="h-5 w-5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $h }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- CTA Banner --}}
    @if($sectionVis['cta'] ?? true)
    <section class="relative overflow-hidden bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 py-20 text-white">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-indigo-500/10 blur-3xl"></div>
        </div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8 reveal">
            <h2 class="mb-6 text-4xl font-bold">{{ site_ui('home.cta_banner_title') }}</h2>
            <p class="mx-auto mb-10 max-w-3xl text-xl text-blue-100">{{ site_ui('home.cta_banner_intro') }}</p>
            <div class="flex flex-col justify-center gap-4 sm:flex-row">
                <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-10 py-4 text-lg font-semibold text-blue-800 shadow-lg transition-all hover:bg-gray-100 hover:shadow-xl">
                    {{ site_ui('home.cta_apply') }}
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="{{ route('site.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-10 py-4 text-lg font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                    {{ site_ui('home.cta_contact') }}
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- Partner/Logo Strip --}}
    @if($sectionVis['partners'] ?? true)
    <section class="bg-white py-12 border-t border-slate-100">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="mb-8 text-center text-sm font-semibold uppercase tracking-wider text-slate-400 reveal">{{ __('Our Partners & Affiliations') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-8 md:gap-16 reveal">
                <div class="h-12 w-32 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 text-sm font-medium">Logo 1</div>
                <div class="h-12 w-32 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 text-sm font-medium">Logo 2</div>
                <div class="h-12 w-32 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 text-sm font-medium">Logo 3</div>
                <div class="h-12 w-32 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 text-sm font-medium">Logo 4</div>
                <div class="h-12 w-32 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 text-sm font-medium">Logo 5</div>
            </div>
        </div>
    </section>
    @endif
@endsection

@push('scripts')
<script>
(function(){
    document.querySelectorAll('.notice-scroll-container').forEach(function(el){
        var speed = parseInt(el.dataset.scrollSpeed || '15', 10);
        var content = el.querySelector('.notice-scroll-content');
        if(content) content.style.setProperty('--scroll-duration', speed + 's');
    });
})();
</script>
@endpush
