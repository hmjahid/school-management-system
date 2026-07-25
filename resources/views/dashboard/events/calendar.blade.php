@extends('layouts.dashboard')

@section('title', __('School Calendar'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('dashboard.events') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Events') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ __('School Calendar') }} — {{ $anchor->format('F Y') }}</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.events.calendar', ['month' => $anchor->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">← {{ __('Prev') }}</a>
            <a href="{{ route('dashboard.events.calendar', ['month' => now()->format('Y-m')]) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Today') }}</a>
            <a href="{{ route('dashboard.events.calendar', ['month' => $anchor->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Next') }} →</a>
        </div>
    </div>

    {{-- Legend --}}
    <div class="mb-4 flex flex-wrap gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Legend') }}:</span>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded-sm bg-blue-500"></span>
            <span class="text-xs text-gray-700 dark:text-gray-300">{{ __('Events') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded-sm bg-red-500"></span>
            <span class="text-xs text-gray-700 dark:text-gray-300">{{ __('Government Holidays') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded-sm bg-green-500"></span>
            <span class="text-xs text-gray-700 dark:text-gray-300">{{ __('Academic') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded-sm bg-orange-500"></span>
            <span class="text-xs text-gray-700 dark:text-gray-300">{{ __('School Activities') }}</span>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
                <div class="px-2 py-2 text-center">{{ __($d) }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            @php
                $cursor = $start->copy();
            @endphp
            @while ($cursor->lte($end))
                @php
                    $inMonth = $cursor->month === $anchor->month;
                    $isToday = $cursor->isToday();
                    $key = $cursor->format('Y-m-d');
                    $dayItems = $byDay[$key] ?? [];
                @endphp
                <div class="min-h-28 border-b border-r border-gray-100 p-2 text-xs dark:border-gray-700 {{ $inMonth ? '' : 'bg-gray-50 text-gray-400 dark:bg-gray-900/50 dark:text-gray-600' }}">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="{{ $isToday ? 'rounded-full bg-blue-600 px-2 py-0.5 text-white font-bold' : 'font-medium text-gray-700 dark:text-gray-300' }}">{{ $cursor->day }}</span>
                    </div>
                    @foreach ($dayItems as $item)
                        @if ($item['type'] === 'event')
                            <a href="{{ route('dashboard.events.edit', $item['model']) }}" class="mb-1 block truncate rounded bg-blue-50 px-1.5 py-0.5 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60" title="{{ $item['title'] }}">
                                {{ $item['title'] }}
                            </a>
                        @elseif ($item['type'] === 'holiday')
                            <div class="mb-1 block truncate rounded bg-red-50 px-1.5 py-0.5 text-red-700 dark:bg-red-900/40 dark:text-red-300" title="{{ $item['title'] }}">
                                🏖️ {{ $item['title'] }}
                            </div>
                        @elseif ($item['type'] === 'academic')
                            <div class="mb-1 block truncate rounded bg-green-50 px-1.5 py-0.5 text-green-700 dark:bg-green-900/40 dark:text-green-300" title="{{ $item['title'] }}">
                                📝 {{ $item['title'] }}
                            </div>
                        @elseif ($item['type'] === 'school')
                            <div class="mb-1 block truncate rounded bg-orange-50 px-1.5 py-0.5 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300" title="{{ $item['title'] }}">
                                🏫 {{ $item['title'] }}
                            </div>
                        @endif
                    @endforeach
                </div>
                @php $cursor->addDay(); @endphp
            @endwhile
        </div>
    </div>

    {{-- Upcoming Holidays & Events sidebar cards --}}
    @php
        $upcomingHolidays = collect($holidays)->filter(fn($h) => \Carbon\Carbon::parse($h['date'])->isFuture())->take(5);
        $upcomingEvents = \App\Models\Event::query()->where('start_date', '>=', now())->orderBy('start_date')->take(5)->get();
    @endphp
    @if ($upcomingHolidays->isNotEmpty() || $upcomingEvents->isNotEmpty())
        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            @if ($upcomingHolidays->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                        <span class="inline-block h-3 w-3 rounded-sm bg-red-500"></span>
                        {{ __('Upcoming Holidays') }}
                    </h3>
                    <ul class="space-y-2">
                        @foreach ($upcomingHolidays as $h)
                            <li class="flex items-center justify-between text-xs">
                                <span class="text-gray-700 dark:text-gray-300">{{ $h['name'] }}</span>
                                <span class="whitespace-nowrap rounded bg-red-50 px-1.5 py-0.5 text-red-600 dark:bg-red-900/40 dark:text-red-400">{{ \Carbon\Carbon::parse($h['date'])->format('M d') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($upcomingEvents->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                        <span class="inline-block h-3 w-3 rounded-sm bg-blue-500"></span>
                        {{ __('Upcoming Events') }}
                    </h3>
                    <ul class="space-y-2">
                        @foreach ($upcomingEvents as $ev)
                            <li class="flex items-center justify-between text-xs">
                                <a href="{{ route('dashboard.events.edit', $ev) }}" class="truncate text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400">{{ $ev->title }}</a>
                                <span class="whitespace-nowrap rounded bg-blue-50 px-1.5 py-0.5 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">{{ $ev->start_date->format('M d') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
@endsection
