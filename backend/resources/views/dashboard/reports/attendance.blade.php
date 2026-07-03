@extends('layouts.dashboard')

@section('title', __('Attendance report'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('dashboard.reports') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Reports') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ __('Attendance report') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $from->format('Y-m-d') }} → {{ $to->format('Y-m-d') }}</p>
        </div>
        <form method="get" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('From') }}</label>
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('To') }}</label>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ __('Apply') }}</button>
            <a href="{{ route('dashboard.reports.export', 'attendance') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Export CSV') }}</a>
        </form>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Records') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($total) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Present') }}</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($present) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Rate') }}</p>
            <p class="mt-1 text-2xl font-bold text-blue-600">{{ $rate }}%</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By class') }}</h2>
            <div class="space-y-2">
                @forelse ($byClass as $row)
                    @php $pct = $row->total_count > 0 ? round(100 * $row->present_count / $row->total_count, 1) : 0; @endphp
                    <div class="border-b border-gray-100 py-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-700">{{ $row->class_name ?? '—' }}</span>
                            <span class="text-gray-900">{{ $pct }}% <span class="text-gray-500">({{ $row->present_count }}/{{ $row->total_count }})</span></span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100">
                            <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data in this range.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By date') }}</h2>
            <div class="space-y-2">
                @forelse ($byDate as $row)
                    @php $pct = $row->total_count > 0 ? round(100 * $row->present_count / $row->total_count, 1) : 0; @endphp
                    <div class="border-b border-gray-100 py-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-700">{{ $row->day }}</span>
                            <span class="text-gray-900">{{ $pct }}% <span class="text-gray-500">({{ $row->present_count }}/{{ $row->total_count }})</span></span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100">
                            <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data in this range.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
