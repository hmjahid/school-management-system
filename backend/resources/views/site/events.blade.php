@extends('layouts.app')

@section('title', __('Events & calendar') . ' — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('Events & calendar') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('Upcoming school events, open days, and important dates.') }}</p>

        @if ($upcoming->isNotEmpty())
            <h2 class="mt-10 text-xl font-semibold text-gray-900">{{ __('Upcoming') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($upcoming as $event)
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                            {{ optional($event->start_date)->format('D, M j, Y · H:i') }}
                        </p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">{{ $event->title }}</h3>
                        @if ($event->location)
                            <p class="mt-1 text-sm text-gray-600">{{ $event->location }}{{ $event->is_virtual ? ' · ' . __('Virtual') : '' }}</p>
                        @endif
                        @if ($event->description)
                            <p class="mt-3 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($event->description, 180) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p class="mt-10 rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-500">{{ __('No upcoming events published yet.') }}</p>
        @endif

        @if ($past->isNotEmpty())
            <h2 class="mt-12 text-xl font-semibold text-gray-900">{{ __('Past events') }}</h2>
            <ul class="mt-4 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
                @foreach ($past as $event)
                    <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ $event->title }}</p>
                            <p class="text-xs text-gray-500">{{ $event->location ?: '—' }}</p>
                        </div>
                        <p class="text-sm text-gray-500">{{ optional($event->start_date)->format('M j, Y') }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
