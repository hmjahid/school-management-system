@extends('layouts.app')

@section('title', __('Transport') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    @include('site.partials.inner-hero', [
        'title' => __('Transport'),
        'subtitle' => __('Bus routes, stops, and fare information.'),
    ])

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if($routes->isEmpty())
            <p class="rounded-lg bg-slate-50 px-6 py-12 text-center text-slate-600">{{ __('Transport routes will be published soon.') }}</p>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($routes as $route)
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-slate-500">{{ $route->code }}</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('Active') }}</span>
                        </div>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ $route->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ __('Vehicle') }}: <strong>{{ $route->vehicle?->number ?? '—' }}</strong>
                        </p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ __('Fare') }}: <strong>{{ number_format((float) $route->fare, 2) }}</strong>
                        </p>
                        @if($route->stops->isNotEmpty())
                            <h3 class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Stops') }}</h3>
                            <ol class="mt-2 space-y-1 text-sm text-slate-700">
                                @foreach($route->stops as $stop)
                                    <li class="flex items-center justify-between border-b border-slate-100 pb-1">
                                        <span>{{ $loop->iteration }}. {{ $stop->name }}</span>
                                        <span class="text-xs text-slate-500">
                                            @if($stop->pickup_time) ↑ {{ $stop->pickup_time->format('H:i') }} @endif
                                            @if($stop->drop_time) ↓ {{ $stop->drop_time->format('H:i') }} @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection