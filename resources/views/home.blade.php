@extends('layouts.app')

@section('title', ($homeContent->title ?? site_ui('nav.home')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', ($homeContent->meta_description ?? $siteSettings?->meta_description) ?? '')

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
        $headline = $hero['headline'] ?? site_ui('home.hero_headline');
        $sub = $hero['motto'] ?? $hero['subtitle'] ?? site_ui('home.hero_subtitle');
        $principalMessage = $principal['message'] ?? site_ui('home.principal_message_default');
        $testimonialsFallback = $testimonials ?: site_ui('home.testimonials_default', []);
        $highlightsFallback = $highlights ?: site_ui('home.highlights_default', []);

        $heroDesign = $hc['hero_design'] ?? 'design-1';
        if (! in_array($heroDesign, ['design-1', 'design-2', 'design-3', 'design-4', 'design-5', 'design-6'], true)) {
            $heroDesign = 'design-1';
        }
        $sliderSlides = $hc['slider'] ?? [];
        if (empty($sliderSlides) && $sliderFallback?->isNotEmpty()) {
            $sliderSlides = $sliderFallback;
        }

        $featuresH = $hc['features_heading'] ?? [];
        $statsL = $hc['stats'] ?? [];
        $teachersH = $hc['teachers'] ?? [];
        $testimonialsH = $hc['testimonials_heading'] ?? [];
        $remarkableH = $hc['remarkable_students'] ?? [];
        $committeeH = $hc['committee_members'] ?? [];
        $eventsH = $hc['events'] ?? [];
        $newsH = $hc['news'] ?? [];
        $noticesH = $hc['notices'] ?? [];
        $highlightsH = $hc['highlights_heading'] ?? [];
        $partnersH = $hc['partners_heading'] ?? [];
    @endphp

    {{-- Hero Section (design chosen in CMS) --}}
    @if($sectionVis['hero'] ?? true)
        @include('partials.site.hero.'.$heroDesign, [
            'hero' => $hero,
            'heroImg' => $heroImg,
            'headline' => $headline,
            'sub' => $sub,
            'siteSettings' => $siteSettings,
            'recentNotices' => $recentNotices,
            'noticesH' => $noticesH,
            'sectionVis' => $sectionVis,
            'stats' => $stats,
            'statsL' => $statsL,
            'admissionsOpen' => $admissionsOpen,
            'sliderSlides' => $sliderSlides,
        ])
    @endif

    {{-- Features Section --}}
    @if($sectionVis['features'] ?? true)
    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center reveal">
                <h2 class="mb-4 text-4xl font-bold text-gray-900">{{ $featuresH['title'] ?? site_ui('home.features_title') }}</h2>
                <div class="mx-auto h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{{ $featuresH['intro'] ?? site_ui('home.features_intro') }}</p>
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
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ $statsL['students'] ?? site_ui('home.stats_students') }}</div>
                </div>
                <div class="p-6 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10">
                    <div class="text-4xl font-bold" data-countup data-target="{{ $stats['teachers'] ?? 0 }}" data-suffix="+">0</div>
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ $statsL['faculty'] ?? site_ui('home.stats_faculty') }}</div>
                </div>
                <div class="p-6 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10">
                    <div class="text-4xl font-bold" data-countup data-target="{{ $stats['years'] ?? 0 }}" data-suffix="+">0</div>
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ $statsL['years'] ?? site_ui('home.stats_years') }}</div>
                </div>
                <div class="p-6 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10">
                    <div class="text-4xl font-bold" data-countup data-target="{{ $stats['awards'] ?? 0 }}" data-suffix="+">0</div>
                    <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider">{{ $statsL['awards'] ?? site_ui('home.stats_awards') }}</div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Principal's Message --}}
    @if(($sectionVis['principal'] ?? true) && !empty($principalMessage))
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center reveal">
                    <h2 class="text-3xl font-bold text-gray-900">{{ $principal['section_title'] ?? site_ui('home.principal_title') }}</h2>
                    <div class="mx-auto mt-3 h-1 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600"></div>
                </div>
                <div class="grid items-center gap-12 lg:grid-cols-5 lg:gap-16 reveal">
                    <div class="lg:col-span-2">
                        <div class="relative mx-auto w-full max-w-xs">
                            @if(!empty($principal['photo']))
                                <img src="{{ $principal['photo'] }}" alt="{{ $principal['name'] ?? __('Principal') }}"
                                    class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl ring-1 ring-slate-200">
                            @else
                                <div class="flex aspect-[4/3] w-full items-center justify-center rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100">
                                    <svg class="h-20 w-20 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                </div>
                            @endif
                            @if(!empty($principal['name']))
                                <div class="absolute -bottom-5 left-1/2 w-max -translate-x-1/2 whitespace-nowrap rounded-full bg-gradient-to-r from-orange-500 to-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-lg">
                                    {{ $principal['name'] }}
                                    @if(!empty($principal['designation']))
                                        <span class="font-normal text-orange-100">· {{ $principal['designation'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="relative rounded-2xl border border-orange-100 bg-orange-50/50 p-8 sm:p-10">
                            <svg class="absolute -top-5 -left-4 h-12 w-12 text-orange-300" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.544 6.068 5.982 8.79 5.982 11H10v10H0z"/></svg>
                            <blockquote class="mt-2 text-lg leading-relaxed text-gray-700">{{ $principalMessage }}</blockquote>
                            <div class="mt-6 flex items-center gap-3">
                                <div class="h-1 w-10 rounded-full bg-orange-400"></div>
                                <p class="text-sm font-medium uppercase tracking-wide text-gray-500">{{ $principal['designation'] ?? __('Principal') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Our Teachers --}}
    @if(($sectionVis['teachers'] ?? true) && $teachers->isNotEmpty())
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center reveal">
                <h2 class="mb-4 text-4xl font-bold text-gray-900">{{ $teachersH['title'] ?? site_ui('home.teachers_title') }}</h2>
                <div class="mx-auto h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{{ $teachersH['intro'] ?? site_ui('home.teachers_intro') }}</p>
            </div>
            <div class="relative" data-teachers-slider>
                <button type="button" data-teachers-prev class="absolute -left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-left-5" aria-label="Previous">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" data-teachers-next class="absolute -right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-right-5" aria-label="Next">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div data-teachers-track class="flex gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-4 -mx-2 px-2 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    @foreach($teachers as $teacher)
                        @php
                            $name = $teacher->user?->name ?? __('Teacher');
                            $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
                        @endphp
                        <div class="w-full min-w-0 snap-start shrink-0 md:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)] group rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 text-center transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 ring-4 ring-white shadow-lg transition-transform duration-300 group-hover:scale-105">
                                {{ $initials }}
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ $name }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $teacher->qualification ?? __('Teacher') }}</p>
                            @if($teacher->subjects)
                                <p class="mt-2 text-xs text-gray-400">{{ is_array($teacher->subjects) ? implode(', ', $teacher->subjects) : $teacher->subjects }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-10 text-center reveal">
                <a href="{{ route('site.faculty') }}" class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:text-blue-800 transition-colors">
                    {{ $teachersH['view_all'] ?? site_ui('home.teachers_view_all') }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- Managing Committee --}}
    @if($sectionVis['committee_members'] ?? true)
    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center reveal">
                <h2 class="mb-4 text-4xl font-bold text-gray-900">{{ $committeeH['title'] ?? site_ui('home.committee_title') }}</h2>
                <div class="mx-auto h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{{ $committeeH['intro'] ?? site_ui('home.committee_intro') }}</p>
            </div>
            @if($committeeMembers->isNotEmpty())
            <div class="relative" data-committee-slider>
                <button type="button" data-committee-prev class="absolute -left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-left-5" aria-label="Previous">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" data-committee-next class="absolute -right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-right-5" aria-label="Next">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div data-committee-track class="flex gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-4 -mx-2 px-2 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    @foreach($committeeMembers as $member)
                        @php
                            $name = $member->localizedName();
                            $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
                            $photo = $member->photo_url;
                        @endphp
                        <div class="w-full min-w-0 snap-start shrink-0 md:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)] group rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 text-center transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 ring-4 ring-white shadow-lg transition-transform duration-300 group-hover:scale-105 overflow-hidden">
                                @if($photo)
                                    <img src="{{ $photo }}" alt="{{ $name }}" class="h-full w-full object-cover">
                                @else
                                    {{ $initials }}
                                @endif
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ $name }}</h3>
                            <p class="mt-1 text-sm text-blue-600 font-medium">{{ $member->localizedDesignation() }}</p>
                            @if($member->phone)
                                <p class="mt-2 text-xs text-gray-400 flex items-center justify-center gap-1">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                                    {{ $member->phone }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="py-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                <p class="mt-4 text-gray-500">{{ __('Committee members will be displayed here once added in the dashboard.') }}</p>
            </div>
            @endif
            <div class="mt-10 text-center reveal">
                <a href="{{ route('site.committee') }}" class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:text-blue-800 transition-colors">
                    {{ $committeeH['view_all'] ?? site_ui('home.committee_view_all') }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- Testimonials --}}
    @if(($sectionVis['testimonials'] ?? true) && count($testimonialsFallback))
        <section class="bg-slate-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-14 text-center reveal">
                    <h2 class="mb-4 text-4xl font-bold text-gray-900">{{ $testimonialsH['title'] ?? site_ui('home.testimonials_title') }}</h2>
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

    {{-- Remarkable Students --}}
    @if(($sectionVis['remarkable_students'] ?? true) && $remarkableStudents->isNotEmpty())
    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center reveal">
                <h2 class="mb-4 text-4xl font-bold text-gray-900">{{ $remarkableH['title'] ?? site_ui('home.remarkable_students_title') }}</h2>
                <div class="mx-auto h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{{ $remarkableH['intro'] ?? site_ui('home.remarkable_students_intro') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($remarkableStudents as $student)
                    @php
                        $name = $student->user?->name ?? __('Student');
                        $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
                    @endphp
                    <div class="group rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 text-center transition-all duration-300 hover:shadow-xl hover:-translate-y-1 reveal">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-amber-100 to-orange-100 text-2xl font-bold text-orange-600 ring-4 ring-white shadow-lg transition-transform duration-300 group-hover:scale-105">
                            {{ $initials }}
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ $name }}</h3>
                        @if($student->class)
                            <p class="mt-1 text-sm text-gray-500">{{ $student->class->name }}</p>
                        @endif
                        @if($student->achievement)
                            <p class="mt-2 text-xs text-orange-600 font-medium leading-relaxed">{{ $student->achievement }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Photo Slider Section (CMS-managed) --}}
    @if(($sectionVis['slider'] ?? true) && count($sliderSlides))
        <section class="bg-slate-100 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-4 reveal">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">{{ site_ui('home.slider_title') }}</h2>
                        <div class="mt-2 h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                        <p class="mt-3 max-w-2xl text-gray-600">{{ site_ui('home.slider_intro') }}</p>
                    </div>
                </div>

                <div class="relative" data-slider-carousel>
                    <button type="button" data-slider-prev class="absolute -left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-left-5" aria-label="{{ __('Previous') }}">
                        <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" data-slider-next class="absolute -right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-right-5" aria-label="{{ __('Next') }}">
                        <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <div data-slider-track class="flex gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 -mx-2 px-2 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                        @foreach ($sliderSlides as $slide)
                            @php
                                $img = $slide['image'] ?? ($slide['image_path'] ?? ($slide['image_url'] ?? null));
                                $slideTitle = $slide['title'] ?? '';
                                $caption = $slide['caption'] ?? '';
                                $link = $slide['link'] ?? null;
                            @endphp
                            @if(! empty($img))
                                <div class="group relative w-full min-w-0 shrink-0 snap-start overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-100 md:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]">
                                    <div class="h-60 overflow-hidden bg-slate-200">
                                        <img src="{{ $img }}" alt="{{ $slideTitle }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                    </div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                                    <div class="absolute bottom-0 w-full p-5">
                                        @if($slideTitle)
                                            <h3 class="text-lg font-bold text-white">{{ $slideTitle }}</h3>
                                        @endif
                                        @if($caption)
                                            <p class="mt-1 text-sm text-white/80">{{ $caption }}</p>
                                        @endif
                                    </div>
                                    @if($link)
                                        <a href="{{ $link }}" class="absolute inset-0" aria-label="{{ $slideTitle }}"></a>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
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
                        <h2 class="text-3xl font-bold text-gray-900">{{ $eventsH['title'] ?? site_ui('home.events_title') }}</h2>
                        <div class="mt-2 h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                    </div>
                    <a href="{{ route('site.news') }}" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">{{ $eventsH['view_all'] ?? site_ui('home.events_view_all') }} <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
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
                        <h2 class="text-3xl font-bold text-gray-900">{{ $newsH['title'] ?? site_ui('home.news_title') }}</h2>
                        <div class="mt-2 h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                    </div>
                    <a href="{{ route('site.news') }}" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">{{ $newsH['view_all'] ?? site_ui('home.news_view_all') }} <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
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
                <h2 class="mb-4 text-center text-2xl font-bold text-gray-900 reveal">{{ $highlightsH['title'] ?? site_ui('home.highlights_title') }}</h2>
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
    @php $partners = $homeContent->content['partners'] ?? []; @endphp
    @if(count($partners))
    <section class="bg-white py-12 border-t border-slate-100">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="mb-8 text-center text-sm font-semibold uppercase tracking-wider text-slate-400 reveal">{{ $partnersH['title'] ?? site_ui('home.our_partners') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-8 md:gap-14 reveal">
                @foreach($partners as $partner)
                    @php
                        $color = $partner['color'] ?? 'blue';
                        $icon = $partner['icon'] ?? 'book';
                        $colorMap = [
                            'blue' => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'text' => 'text-blue-600 dark:text-blue-400'],
                            'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                            'amber' => ['bg' => 'bg-amber-50 dark:bg-amber-900/20', 'text' => 'text-amber-600 dark:text-amber-400'],
                            'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/20', 'text' => 'text-purple-600 dark:text-purple-400'],
                            'rose' => ['bg' => 'bg-rose-50 dark:bg-rose-900/20', 'text' => 'text-rose-600 dark:text-rose-400'],
                        ];
                        $c = $colorMap[$color] ?? $colorMap['blue'];
                        $svgs = [
                            'book' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
                            'school' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                            'award' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>',
                            'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                            'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
                        ];
                        $svgPath = $svgs[$icon] ?? $svgs['book'];
                    @endphp
                    <a href="{{ $partner['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 opacity-60 transition hover:opacity-100" title="{{ $partner['name'] ?? '' }}">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full {{ $c['bg'] }}">
                            <svg class="h-7 w-7 {{ $c['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $svgPath !!}</svg>
                        </div>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $partner['name'] ?? '' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif
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

    var slider = document.querySelector('[data-teachers-slider]');
    if (slider) {
        var track = slider.querySelector('[data-teachers-track]');
        var prev = slider.querySelector('[data-teachers-prev]');
        var next = slider.querySelector('[data-teachers-next]');
        if (track && prev && next) {
            function getScrollAmount() {
                var w = window.innerWidth;
                if (w >= 1024) return track.clientWidth / 3 + 24;
                if (w >= 768) return track.clientWidth / 2 + 24;
                return track.clientWidth;
            }
            prev.addEventListener('click', function() { track.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' }); });
            next.addEventListener('click', function() { track.scrollBy({ left: getScrollAmount(), behavior: 'smooth' }); });
            var autoScroll = setInterval(function() {
                if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    track.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
                }
            }, 4000);
            track.addEventListener('mouseenter', function() { clearInterval(autoScroll); });
            track.addEventListener('touchstart', function() { clearInterval(autoScroll); }, { passive: true });
        }
    }

    var cSlider = document.querySelector('[data-committee-slider]');
    if (cSlider) {
        var cTrack = cSlider.querySelector('[data-committee-track]');
        var cPrev = cSlider.querySelector('[data-committee-prev]');
        var cNext = cSlider.querySelector('[data-committee-next]');
        if (cTrack && cPrev && cNext) {
            function getCommitteeScrollAmount() {
                var w = window.innerWidth;
                if (w >= 1024) return cTrack.clientWidth / 3 + 24;
                if (w >= 768) return cTrack.clientWidth / 2 + 24;
                return cTrack.clientWidth;
            }
            cPrev.addEventListener('click', function() { cTrack.scrollBy({ left: -getCommitteeScrollAmount(), behavior: 'smooth' }); });
            cNext.addEventListener('click', function() { cTrack.scrollBy({ left: getCommitteeScrollAmount(), behavior: 'smooth' }); });
            var committeeAutoScroll = setInterval(function() {
                if (cTrack.scrollLeft + cTrack.clientWidth >= cTrack.scrollWidth - 10) {
                    cTrack.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    cTrack.scrollBy({ left: getCommitteeScrollAmount(), behavior: 'smooth' });
                }
            }, 4000);
            cTrack.addEventListener('mouseenter', function() { clearInterval(committeeAutoScroll); });
            cTrack.addEventListener('touchstart', function() { clearInterval(committeeAutoScroll); }, { passive: true });
        }
    }
})();
</script>
@endpush
