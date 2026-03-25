@extends('layouts.app')

@section('title', ($content->title ?? site_ui('nav.news')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? site_ui('nav.news'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('site.partials.sections', ['content' => $content])

    <section class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900">{{ site_ui('news.list_heading') }}</h2>
        <div class="mt-2 h-1 w-20 bg-orange-500"></div>
        @if($latestNews->isEmpty())
            <p class="mt-4 text-sm text-gray-600">{{ site_ui('news.empty_news') }}</p>
        @else
            <ul class="mt-6 space-y-4">
                @foreach ($latestNews as $item)
                    <li class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <a href="{{ route('site.news.show', $item->slug) }}" class="font-semibold text-blue-700 hover:underline">{{ $item->title }}</a>
                        <p class="mt-2 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit(strip_tags($item->content), 200) }}</p>
                        @if($item->published_at)
                            <time class="mt-2 block text-xs text-gray-500" datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mt-12">
        <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('news.events_heading') }}</h2>
        @if($upcomingEvents->isEmpty() && $newsEvents->isEmpty())
            <p class="mt-4 text-sm text-gray-600">{{ site_ui('news.empty_events') }}</p>
        @else
            <ul class="mt-6 space-y-3">
                @foreach ($upcomingEvents as $ev)
                    <li class="rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <span class="font-medium text-gray-900">{{ $ev->title }}</span>
                        <span class="mt-1 block text-sm text-gray-500">{{ $ev->start_date?->format('M j, Y g:i A') }} @if($ev->location) · {{ $ev->location }} @endif</span>
                    </li>
                @endforeach
                @foreach ($newsEvents as $ev)
                    <li class="rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <span class="font-medium text-gray-900">{{ $ev->title }}</span>
                        <span class="mt-1 block text-sm text-gray-500">{{ $ev->event_date?->format('M j, Y') }} @if($ev->event_location) · {{ $ev->event_location }} @endif</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mt-12">
        <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('news.past_events_heading') }}</h2>
        @if($pastEvents->isEmpty())
            <p class="mt-4 text-sm text-gray-600">{{ site_ui('news.past_events_empty') }}</p>
        @else
            <ul class="mt-6 space-y-3">
                @foreach ($pastEvents as $ev)
                    <li class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <span class="font-medium text-gray-800">{{ $ev->title }}</span>
                        <span class="mt-1 block text-sm text-gray-500">{{ $ev->start_date?->format('M j, Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <p class="mt-10 text-sm text-gray-600">{{ site_ui('news.magazine_note') }}</p>
        </div>
    </div>
@endsection
