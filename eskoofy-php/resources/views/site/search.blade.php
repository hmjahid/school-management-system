@extends('layouts.app')

@section('title', __('Search') . ' — ' . config('app.name'))

@section('content')
<section class="bg-gradient-to-br from-blue-600 to-indigo-700 py-16 text-white">
    <div class="mx-auto max-w-4xl px-4 text-center">
        <h1 class="text-3xl font-bold">{{ __('Search') }}</h1>
        <form action="{{ route('site.search') }}" method="GET" class="mt-6">
            <div class="relative mx-auto max-w-2xl">
                <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $query }}" placeholder="{{ site_ui('nav.search_placeholder') }}" autofocus
                    class="w-full rounded-xl border-0 bg-white py-3.5 pl-12 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-white/30 shadow-lg">
            </div>
        </form>
    </div>
</section>

<section class="mx-auto max-w-4xl px-4 py-12">
    @if($query && strlen($query) < 2)
        <p class="text-center text-gray-500">{{ __('Please enter at least 2 characters to search.') }}</p>
    @elseif($results->isEmpty())
        <p class="text-center text-gray-500">{{ __('No results found for') }} "{{ $query }}"</p>
    @else
        {{-- Category filter pills --}}
        <div class="mb-8 flex flex-wrap justify-center gap-2">
            @php
                $filters = [
                    'all' => __('All'),
                    'news' => __('News'),
                    'notice' => __('Notices'),
                    'event' => __('Events'),
                    'page' => __('Pages'),
                ];
            @endphp
            @foreach($filters as $key => $label)
                <a href="{{ route('site.search', array_merge(['q' => $query], $key === 'all' ? [] : ['type' => $key])) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $activeType === $key ? 'bg-blue-600 text-white shadow' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                    {{ $label }}
                    @if(isset($typeCounts[$key]))
                        <span class="opacity-70">({{ $typeCounts[$key] }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        <p class="mb-6 text-sm text-gray-500">{{ $results->count() }} {{ __('results found for') }} "<strong>{{ $query }}</strong>"</p>

        <div class="space-y-8">
            @foreach($grouped as $groupKey => $items)
                <div>
                    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        <span class="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">{{ $items->first()['type'] }}</span>
                        {{ $items->count() }}
                    </h2>
                    <div class="space-y-4">
                        @foreach($items as $item)
                            <a href="{{ $item['url'] }}" class="group block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-blue-200">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-block rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $item['type'] }}</span>
                                    @if($item['date'])
                                        <span class="text-xs text-gray-400">{{ $item['date'] }}</span>
                                    @endif
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600">{{ $item['title'] }}</h2>
                                @if($item['excerpt'])
                                    <p class="mt-1 text-sm text-gray-500">{{ $item['excerpt'] }}</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection