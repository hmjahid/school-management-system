@extends('layouts.app')

@section('title', __('Events & calendar') . ' — ' . ($siteSettings->site_name ?? config('app.name')))
@section('meta_description', __('Upcoming school events, open days, and important dates.'))

@section('content')
    @if($siteSettings->section_visibility['events_hero'] ?? true)
    <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold md:text-5xl">{{ __('Events & Calendar') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ __('Upcoming school events, open days, and important dates.') }}</p>
        </div>
    </div>
    @endif

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if($siteSettings->section_visibility['events_filters'] ?? true)
        {{-- View toggle and filters --}}
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4 reveal">
            <div class="flex flex-wrap gap-2">
                <button type="button" data-filter="all" class="rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">{{ __('All') }}</button>
                <button type="button" data-filter="upcoming" class="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">{{ __('Upcoming') }}</button>
                <button type="button" data-filter="past" class="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">{{ __('Past') }}</button>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-1">
                <button type="button" data-view="grid" class="rounded-md px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 data-[active=true]:bg-blue-600 data-[active=true]:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </button>
                <button type="button" data-view="list" class="rounded-md px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 data-[active=true]:bg-blue-600 data-[active=true]:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
        @endif

        @if($siteSettings->section_visibility['events_upcoming'] ?? true)
        {{-- Upcoming events grid --}}
        @if ($upcoming->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" data-event-grid>
                @foreach ($upcoming as $event)
                    <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl reveal" data-event-type="upcoming">
                        <div class="absolute right-0 top-0 h-20 w-20 translate-x-6 -translate-y-6 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 opacity-50"></div>
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex flex-col items-center rounded-xl bg-gradient-to-b from-orange-400 to-orange-600 px-4 py-2 text-white shadow-lg">
                                <span class="text-2xl font-bold leading-none">{{ $event->start_date?->format('d') }}</span>
                                <span class="text-xs font-semibold uppercase">{{ $event->start_date?->format('M') }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-orange-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                                {{ __('Upcoming') }}
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $event->title }}</h3>
                        <div class="mt-3 space-y-1.5 text-sm text-slate-500">
                            @if($event->start_date)
                                <p class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <time datetime="{{ $event->start_date->toIso8601String() }}">{{ $event->start_date->format('D, M j, Y · H:i') }}</time>
                                </p>
                            @endif
                            @if($event->location)
                                <p class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $event->location }}{{ $event->is_virtual ? ' · ' . __('Virtual') : '' }}
                                </p>
                            @endif
                            @if($event->start_date)
                                <p class="flex items-center gap-1.5 text-blue-600 font-medium" data-countdown="{{ $event->start_date->toIso8601String() }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span data-countdown-display></span>
                                </p>
                            @endif
                        </div>
                        @if ($event->description)
                            <p class="mt-4 text-sm text-slate-600 leading-relaxed">{{ \Illuminate\Support\Str::limit($event->description, 150) }}</p>
                        @endif
                        <div class="mt-5 flex items-center gap-3">
                            <button onclick="alert('{{ __('Add to Calendar') }}')" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                {{ __('Add to Calendar') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="mt-4 text-sm text-slate-500">{{ __('No upcoming events published yet.') }}</p>
            </div>
        @endif
        @endif

        @if($siteSettings->section_visibility['events_past'] ?? true)
        {{-- Past events --}}
        @if ($past->isNotEmpty())
            <section class="mt-16 reveal">
                <h2 class="text-2xl font-bold text-slate-900">{{ __('Past Events') }}</h2>
                <div class="mt-2 h-1 w-16 bg-gradient-to-r from-slate-400 to-slate-500 rounded-full"></div>
                <div class="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm" data-event-grid>
                    @foreach ($past as $event)
                        <div class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:justify-between transition hover:bg-slate-50" data-event-type="past">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                    <span class="text-sm font-bold leading-none">{{ $event->start_date?->format('d') }}</span>
                                    <span class="text-[10px] font-semibold uppercase">{{ $event->start_date?->format('M') }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">{{ $event->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $event->location ?: '—' }}</p>
                                </div>
                            </div>
                            <p class="text-sm text-slate-500">{{ $event->start_date?->format('M j, Y') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
        @endif
    </div>
@endsection
