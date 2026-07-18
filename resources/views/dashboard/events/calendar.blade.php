@extends('layouts.dashboard')

@section('title', __('Events calendar'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('dashboard.events') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Events') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $anchor->format('F Y') }}</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.events.calendar', ['month' => $anchor->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">← {{ __('Prev') }}</a>
            <a href="{{ route('dashboard.events.calendar', ['month' => now()->format('Y-m')]) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Today') }}</a>
            <a href="{{ route('dashboard.events.calendar', ['month' => $anchor->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Next') }} →</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500">
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
                    $dayEvents = $byDay[$key] ?? [];
                @endphp
                <div class="min-h-28 border-b border-r border-gray-100 p-2 text-xs {{ $inMonth ? '' : 'bg-gray-50 text-gray-400' }}">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="{{ $isToday ? 'rounded-full bg-blue-600 px-2 py-0.5 text-white' : 'font-medium' }}">{{ $cursor->day }}</span>
                    </div>
                    @foreach ($dayEvents as $e)
                        <a href="{{ route('dashboard.events.edit', $e) }}" class="mb-1 block truncate rounded bg-blue-50 px-1.5 py-0.5 text-blue-700 hover:bg-blue-100" title="{{ $e->title }}">
                            {{ $e->title }}
                        </a>
                    @endforeach
                </div>
                @php $cursor->addDay(); @endphp
            @endwhile
        </div>
    </div>
@endsection
