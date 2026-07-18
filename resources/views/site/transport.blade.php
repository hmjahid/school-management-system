@extends('layouts.app')

@section('title', __('Transport') . ' — ' . ($siteSettings->site_name ?? config('app.name')))
@section('meta_description', __('Bus routes, stops, and fare information.'))

@section('content')
    <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold md:text-5xl">{{ __('Transport') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ __('Bus routes, stops, and fare information.') }}</p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if($routes->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <p class="mt-4 text-sm text-slate-500">{{ __('Transport routes will be published soon.') }}</p>
            </div>
        @else
            {{-- Route tabs/pills --}}
            @php $routeNames = $routes->pluck('name')->unique(); @endphp
            <div class="mb-10 flex flex-wrap gap-2 reveal" data-route-tabs>
                <button type="button" data-filter="all" class="rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">{{ __('All Routes') }}</button>
                @foreach($routes as $route)
                    <button type="button" data-filter="{{ \Illuminate\Support\Str::slug($route->name) }}" class="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                        {{ $route->name }}
                    </button>
                @endforeach
            </div>

            {{-- Route cards --}}
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" data-route-grid>
                @foreach($routes as $route)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-xl reveal" data-route-card data-category="{{ \Illuminate\Support\Str::slug($route->name) }}">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-slate-400">{{ $route->code ?? 'RT-' . str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ __('Active') }}
                            </span>
                        </div>
                        <h2 class="mt-3 text-lg font-semibold text-slate-900">{{ $route->name }}</h2>

                        {{-- Vehicle info --}}
                        <div class="mt-4 flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">{{ __('Vehicle') }}</p>
                                <p class="text-sm font-medium text-slate-900">{{ $route->vehicle?->number ?? '—' }}</p>
                            </div>
                            <div class="ml-auto text-right">
                                <p class="text-xs text-slate-500">{{ __('Fare') }}</p>
                                <p class="text-sm font-bold text-blue-600">৳ {{ number_format((float) $route->fare, 2) }}</p>
                            </div>
                        </div>

                        {{-- Stops timeline --}}
                        @if($route->stops->isNotEmpty())
                            <div class="mt-5">
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">{{ __('Stops & Timings') }}</h3>
                                <div class="space-y-0">
                                    @foreach($route->stops as $stop)
                                        <div class="flex items-start gap-3 pb-3 {{ !$loop->last ? 'border-l-2 border-blue-200 ml-[11px] pl-4' : 'ml-4 pl-4' }}">
                                            <div class="absolute -ml-[18px] mt-1.5 h-3 w-3 rounded-full border-2 border-blue-500 bg-white"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-slate-900">{{ $stop->name }}</p>
                                                <p class="text-xs text-slate-500">
                                                    @if($stop->pickup_time) <span class="font-medium text-blue-600">{{ __('Pickup') }}: {{ $stop->pickup_time->format('H:i') }}</span> @endif
                                                    @if($stop->pickup_time && $stop->drop_time) <span class="mx-1">|</span> @endif
                                                    @if($stop->drop_time) <span class="font-medium text-orange-600">{{ __('Drop') }}: {{ $stop->drop_time->format('H:i') }}</span> @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Vehicle fleet section --}}
            <section class="mt-16 reveal">
                <h2 class="text-2xl font-bold text-slate-900">{{ __('Our Fleet') }}</h2>
                <div class="mt-2 h-1 w-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Standard Bus') }}</h3>
                        <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                            <li>{{ __('Capacity') }}: 50 {{ __('seats') }}</li>
                            <li>{{ __('AC/Non-AC') }}: {{ __('Both available') }}</li>
                            <li>{{ __('GPS tracked') }}</li>
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Mini Bus') }}</h3>
                        <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                            <li>{{ __('Capacity') }}: 30 {{ __('seats') }}</li>
                            <li>{{ __('Ideal for shorter routes') }}</li>
                            <li>{{ __('CCTV equipped') }}</li>
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Micro Bus') }}</h3>
                        <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                            <li>{{ __('Capacity') }}: 14 {{ __('seats') }}</li>
                            <li>{{ __('For special trips') }}</li>
                            <li>{{ __('Flexible scheduling') }}</li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Route map placeholder --}}
            <section class="mt-16 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 reveal">
                <div class="flex h-64 items-center justify-center bg-slate-200 md:h-80">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <p class="mt-4 text-sm font-medium text-slate-500">{{ __('Route Map') }}</p>
                        <p class="text-xs text-slate-400">{{ __('Interactive map will be available soon.') }}</p>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
