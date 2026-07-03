@extends('layouts.dashboard')

@section('title', __('Financial report'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('dashboard.reports') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Reports') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ __('Financial report') }}</h1>
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
            <a href="{{ route('dashboard.reports.export', 'fees') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Export CSV') }}</a>
        </form>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Total collected') }}</p>
            <p class="mt-1 text-3xl font-bold text-emerald-600">{{ number_format($total, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Payments') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($count) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By month') }}</h2>
            <div class="space-y-2">
                @forelse ($byMonth as $row)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ $row->bucket ?: '—' }}</span>
                        <span class="text-gray-900">{{ number_format((float) $row->total, 2) }} · <span class="text-gray-500">({{ $row->count }})</span></span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data in this range.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By status') }}</h2>
            <div class="space-y-2">
                @forelse ($byStatus as $row)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ ucfirst($row->payment_status ?? 'unknown') }}</span>
                        <span class="text-gray-900">{{ number_format((float) $row->total, 2) }} · <span class="text-gray-500">({{ $row->count }})</span></span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data in this range.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By payment method') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($byMethod as $row)
                    <div class="rounded-lg border border-gray-100 px-4 py-3">
                        <p class="text-xs font-medium uppercase text-gray-500">{{ $row->payment_method ?: 'unknown' }}</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format((float) $row->total, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $row->count }} {{ __('payments') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data in this range.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
