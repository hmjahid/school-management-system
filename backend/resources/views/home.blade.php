@extends('layouts.app')

@section('title', ($homeContent->title ?? __('Home')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
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
            $features = [
                ['title' => __('Experienced faculty'), 'description' => __('Dedicated educators with deep subject expertise and care for every learner.')],
                ['title' => __('Modern facilities'), 'description' => __('Classrooms and labs that support hands-on, collaborative learning.')],
                ['title' => __('Balanced curriculum'), 'description' => __('Academic rigour alongside arts, sports, and character development.')],
                ['title' => __('Inclusive community'), 'description' => __('A welcoming environment where diversity is celebrated.')],
            ];
        }
        $heroImg = $hero['background_image'] ?? null;
        if (! $heroImg) {
            $heroImg = 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1920&q=80';
        }
        $headline = $hero['headline'] ?? __('Shaping future leaders through excellence in education');
        $sub = $hero['motto'] ?? $hero['subtitle'] ?? __('Empowering students with knowledge, skills, and values to succeed in a changing world.');
    @endphp

    {{-- Hero (archive HomePage.jsx) --}}
    <section class="relative overflow-hidden bg-blue-800 text-white">
        <div class="absolute inset-0">
            <div class="absolute inset-0 z-10 bg-black/50"></div>
            <img src="{{ $heroImg }}" alt="" class="h-full w-full object-cover" loading="eager" width="1920" height="1080">
        </div>
        <div class="relative z-20 mx-auto max-w-7xl px-4 py-24 sm:px-6 md:py-32 lg:px-8">
            <div class="text-center">
                <h1 class="mb-6 text-4xl font-bold tracking-tight md:text-5xl lg:text-6xl">{{ $headline }}</h1>
                <p class="mx-auto mb-8 max-w-3xl text-xl md:text-2xl text-blue-100">{{ $sub }}</p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('admissions.apply') }}" class="inline-block rounded-md bg-orange-500 px-8 py-4 text-lg font-semibold text-white transition-colors duration-300 hover:bg-orange-600">
                        {{ __('Apply for admission') }}
                    </a>
                    <a href="{{ route('site.about') }}" class="inline-block rounded-md border-2 border-white bg-transparent px-8 py-3.5 text-lg font-semibold text-white transition-colors duration-300 hover:bg-white/10">
                        {{ __('Learn more') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Quick links --}}
    <section class="border-b border-gray-200 bg-white py-10">
        <div class="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-3 sm:px-6 lg:px-8">
            <a href="{{ route('admissions.apply') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-md transition-shadow hover:shadow-lg">
                <p class="font-semibold text-gray-900">{{ __('Admissions') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ __('Online application and documents') }}</p>
            </a>
            <a href="{{ route('portal') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-md transition-shadow hover:shadow-lg">
                <p class="font-semibold text-gray-900">{{ __('Parent / student portal') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ __('Attendance, fees, and assignments') }}</p>
            </a>
            <a href="{{ route('admissions.status') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-md transition-shadow hover:shadow-lg">
                <p class="font-semibold text-gray-900">{{ __('Application status') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ __('Track with your application number') }}</p>
            </a>
        </div>
    </section>

    {{-- Features --}}
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900">{{ __('Why choose our school?') }}</h2>
                <div class="mx-auto h-1 w-20 bg-orange-500"></div>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600">
                    {{ __('We foster academic excellence and personal growth in a supportive environment.') }}
                </p>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $index => $feature)
                    <div class="rounded-lg bg-white p-6 shadow-md transition-shadow duration-300 hover:shadow-lg">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-2xl text-blue-600 font-bold">{{ $index + 1 }}</div>
                        <h3 class="mb-2 text-center text-xl font-semibold text-gray-900">{{ $feature['title'] ?? '' }}</h3>
                        <p class="text-center text-gray-600">{{ $feature['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Statistics bar --}}
    @if($stats['students'] || $stats['teachers'] || $stats['years'] !== null)
        <section class="bg-blue-900 py-16 text-white">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    @if($stats['students'])
                        <div>
                            <div class="mb-2 text-4xl font-bold">{{ number_format($stats['students']) }}</div>
                            <div class="text-blue-200">{{ __('Students') }}</div>
                        </div>
                    @endif
                    @if($stats['teachers'])
                        <div>
                            <div class="mb-2 text-4xl font-bold">{{ number_format($stats['teachers']) }}</div>
                            <div class="text-blue-200">{{ __('Faculty') }}</div>
                        </div>
                    @endif
                    @if($stats['years'] !== null)
                        <div>
                            <div class="mb-2 text-4xl font-bold">{{ $stats['years'] }}+</div>
                            <div class="text-blue-200">{{ __('Years of excellence') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if(!empty($principal['message']))
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-4 text-center text-3xl font-bold text-gray-900">{{ __('Welcome from the principal') }}</h2>
                <div class="mx-auto mb-6 h-1 w-20 bg-orange-500"></div>
                <p class="mx-auto max-w-3xl whitespace-pre-line text-center text-lg text-gray-600">{{ $principal['message'] }}</p>
                @if(!empty($principal['name']))
                    <p class="mt-6 text-center font-semibold text-gray-900">— {{ $principal['name'] }}</p>
                @endif
            </div>
        </section>
    @endif

    @if(count($testimonials))
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">{{ __('What parents & students say') }}</h2>
                    <div class="mx-auto h-1 w-20 bg-orange-500"></div>
                </div>
                <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-2">
                    @foreach ($testimonials as $t)
                        <div class="rounded-lg bg-gray-50 p-8 shadow-md">
                            <p class="mb-4 text-4xl text-orange-500">“</p>
                            <p class="mb-6 text-lg text-gray-700">{{ $t['quote'] ?? '' }}</p>
                            <div class="flex items-center">
                                <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-600">
                                    {{ \Illuminate\Support\Str::substr($t['name'] ?? 'A', 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $t['name'] ?? '' }}</h4>
                                    <p class="text-gray-600">{{ $t['role'] ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($upcomingEvents->isNotEmpty())
        <section class="border-t border-gray-100 bg-gray-50 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ __('Upcoming events') }}</h2>
                        <div class="mt-2 h-1 w-20 bg-orange-500"></div>
                    </div>
                    <a href="{{ route('site.news') }}" class="inline-flex items-center font-medium text-blue-600 hover:text-blue-800">{{ __('View all') }} →</a>
                </div>
                <ul class="space-y-3">
                    @foreach ($upcomingEvents as $ev)
                        <li class="flex flex-wrap items-baseline justify-between gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                            <span class="font-medium text-gray-900">{{ $ev->title }}</span>
                            <time class="text-sm text-gray-500" datetime="{{ $ev->start_date?->toIso8601String() }}">{{ $ev->start_date?->format('M j, Y g:i A') }}</time>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if($latestNews->isNotEmpty())
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">{{ __('Latest news & events') }}</h2>
                        <div class="mt-2 h-1 w-20 bg-orange-500"></div>
                    </div>
                    <a href="{{ route('site.news') }}" class="inline-flex items-center font-medium text-blue-600 hover:text-blue-800">
                        {{ __('View all news') }}
                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestNews as $item)
                        <div class="overflow-hidden rounded-lg bg-white shadow-md transition-shadow duration-300 hover:shadow-lg">
                            <div class="h-48 overflow-hidden bg-gray-200">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover transition-transform duration-500 hover:scale-105" loading="lazy">
                                @endif
                            </div>
                            <div class="p-6">
                                @if($item->published_at)
                                    <div class="mb-2 text-sm text-gray-500">{{ $item->published_at->format('M j, Y') }}</div>
                                @endif
                                <h3 class="mb-2 text-xl font-semibold text-gray-900">{{ $item->title }}</h3>
                                <p class="mb-4 text-gray-600">{{ \Illuminate\Support\Str::limit(strip_tags($item->content), 120) }}</p>
                                <a href="{{ route('site.news.show', $item->slug) }}" class="inline-flex items-center font-medium text-blue-600 hover:text-blue-800">
                                    {{ __('Read more') }}
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(count($highlights))
        <section class="bg-white py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-4 text-center text-2xl font-bold text-gray-900">{{ __('Highlights') }}</h2>
                <div class="mx-auto mb-8 h-1 w-20 bg-orange-500"></div>
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach ($highlights as $h)
                        <li class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-gray-700">{{ $h }}</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="bg-blue-700 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="mb-6 text-3xl font-bold">{{ __('Ready to join our community?') }}</h2>
            <p class="mx-auto mb-8 max-w-3xl text-xl text-blue-100">
                {{ __('Take the first step towards an exceptional educational journey.') }}
            </p>
            <div class="flex flex-col justify-center gap-4 sm:flex-row">
                <a href="{{ route('admissions.apply') }}" class="inline-block rounded-md bg-white px-8 py-3 text-lg font-semibold text-blue-700 transition-colors hover:bg-gray-100">
                    {{ __('Apply now') }}
                </a>
                <a href="{{ route('site.contact') }}" class="inline-block rounded-md border-2 border-white px-8 py-3 text-lg font-semibold text-white transition-colors hover:bg-white/10">
                    {{ __('Contact us') }}
                </a>
            </div>
        </div>
    </section>
@endsection
