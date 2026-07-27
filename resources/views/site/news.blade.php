@extends('layouts.app')

@section('title', ($content->title ?? site_ui('nav.news')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @if($siteSettings->section_visibility['news_hero'] ?? true)
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold md:text-5xl">{{ $content->title ?? site_ui('nav.news') }}</h1>
                @if($content->meta_description ?? false)
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ $content->meta_description }}</p>
                @endif
            </div>
        </div>
        @endif

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            {{-- Magazine-style news grid --}}
            @if($latestNews->isNotEmpty())
                @if($siteSettings->section_visibility['news_featured'] ?? true)
                {{-- Featured hero article --}}
                @php $featured = $latestNews->shift(); @endphp
                <section class="mb-12 reveal">
                    <a href="{{ route('site.news.show', $featured->slug) }}" class="group block overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl">
                        <div class="grid md:grid-cols-2">
                            <div class="h-64 md:h-full overflow-hidden bg-slate-200">
                                @if($featured->image_url)
                                    <img src="{{ $featured->image_url }}" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="eager">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                                        <svg class="h-16 w-16 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-col justify-center p-8 lg:p-12">
                                @if($featured->published_at)
                                    <div class="mb-3 flex items-center gap-3 text-sm text-slate-500">
                                        <time datetime="{{ $featured->published_at->toIso8601String() }}">{{ $featured->published_at->format('M j, Y') }}</time>
                                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $featured->category ?? __('Featured') }}</span>
                                    </div>
                                @endif
                                <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl group-hover:text-blue-600 transition-colors">{{ $featured->title }}</h2>
                                <p class="mt-4 text-slate-600 leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($featured->content), 200) }}</p>
                                <span class="mt-6 inline-flex items-center gap-1 font-medium text-blue-600 group-hover:text-blue-800">
                                    {{ site_ui('home.read_more') }}
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                </section>
                @endif

                @if($siteSettings->section_visibility['news_grid'] ?? true)
                {{-- Grid of remaining articles --}}
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestNews as $item)
                        <div class="group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl reveal">
                            <div class="h-48 overflow-hidden bg-slate-200 relative">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                                        <svg class="h-10 w-10 text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                    </div>
                                @endif
                                @if($item->category ?? false)
                                    <span class="absolute top-3 left-3 rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">{{ $item->category }}</span>
                                @endif
                            </div>
                            <div class="p-5">
                                @if($item->published_at)
                                    <time class="text-xs text-slate-500" datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                                @endif
                                <h3 class="mt-2 text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $item->title }}</h3>
                                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($item->content), 120) }}</p>
                                <a href="{{ route('site.news.show', $item->slug) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                                    {{ site_ui('home.read_more') }}
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Load more button --}}
                @if($latestNews->count() >= 9)
                    <div class="mt-12 text-center reveal">
                        <button type="button" data-load-more class="inline-flex items-center gap-2 rounded-xl border-2 border-blue-600 bg-white px-8 py-3 text-sm font-semibold text-blue-700 transition-all hover:bg-blue-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ __('Load More') }}
                        </button>
                    </div>
                @endif
                @endif
            @else
                <div class="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    <p class="mt-4 text-sm text-slate-500">{{ site_ui('news.empty_news') }}</p>
                </div>
            @endif

            {{-- Sidebar-style events --}}
            <div class="mt-16 grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-bold text-slate-900">{{ site_ui('news.events_heading') }}</h2>
                    <div class="mt-2 h-1 w-16 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                    @if($upcomingEvents->isEmpty() && $newsEvents->isEmpty())
                        <p class="mt-6 text-sm text-slate-500">{{ site_ui('news.empty_events') }}</p>
                    @else
                        <div class="mt-6 space-y-3">
                            @foreach ($upcomingEvents as $ev)
                                <div class="flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:shadow-md">
                                    <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gradient-to-b from-orange-400 to-orange-600 text-white">
                                        <span class="text-lg font-bold">{{ $ev->start_date?->format('d') }}</span>
                                        <span class="text-[10px] font-semibold uppercase">{{ $ev->start_date?->format('M') }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-slate-900">{{ $ev->title }}</p>
                                        <p class="text-sm text-slate-500">{{ $ev->start_date?->format('g:i A') }} @if($ev->location) · {{ $ev->location }} @endif</p>
                                    </div>
                                </div>
                            @endforeach
                            @foreach ($newsEvents as $ev)
                                <div class="flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:shadow-md">
                                    <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gradient-to-b from-blue-500 to-indigo-600 text-white">
                                        <span class="text-lg font-bold">{{ $ev->event_date?->format('d') }}</span>
                                        <span class="text-[10px] font-semibold uppercase">{{ $ev->event_date?->format('M') }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-slate-900">{{ $ev->title }}</p>
                                        @if($ev->event_location)<p class="text-sm text-slate-500">{{ $ev->event_location }}</p>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-8">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">{{ __('Categories') }}</h3>
                        <ul class="mt-4 space-y-2">
                            <li><a href="#" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors"><span>{{ __('All News') }}</span><span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs">{{ $latestNews->count() }}</span></a></li>
                            <li><a href="#" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors"><span>{{ __('Academic') }}</span><span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs">4</span></a></li>
                            <li><a href="#" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors"><span>{{ __('Events') }}</span><span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs">6</span></a></li>
                            <li><a href="#" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors"><span>{{ __('Sports') }}</span><span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs">2</span></a></li>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">{{ __('Recent Posts') }}</h3>
                        <ul class="mt-4 space-y-3">
                            @foreach ($latestNews->take(4) as $item)
                                <li>
                                    <a href="{{ route('site.news.show', $item->slug) }}" class="group flex gap-3">
                                        <div class="h-12 w-12 shrink-0 rounded-lg bg-slate-200 overflow-hidden">
                                            @if($item->image_url)
                                                <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-900 group-hover:text-blue-600 transition-colors truncate">{{ $item->title }}</p>
                                            @if($item->published_at)
                                                <p class="text-xs text-slate-500">{{ $item->published_at->format('M j, Y') }}</p>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">{{ __('Archives') }}</h3>
                        <ul class="mt-4 space-y-2">
                            <li><a href="#" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">{{ date('F Y') }}</a></li>
                            <li><a href="#" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">{{ date('F Y', strtotime('-1 month')) }}</a></li>
                            <li><a href="#" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">{{ date('F Y', strtotime('-2 months')) }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Past Events --}}
            @if($pastEvents->isNotEmpty())
                <section class="mt-16 reveal">
                    <h2 class="text-xl font-bold text-slate-900">{{ site_ui('news.past_events_heading') }}</h2>
                    <div class="mt-2 h-1 w-16 bg-gradient-to-r from-slate-400 to-slate-500 rounded-full"></div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($pastEvents as $ev)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 opacity-75">
                                <p class="font-medium text-slate-800">{{ $ev->title }}</p>
                                <time class="mt-1 block text-sm text-slate-500">{{ $ev->start_date?->format('M j, Y') }}</time>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
