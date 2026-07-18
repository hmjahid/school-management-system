@extends('layouts.dashboard')

@section('title', __('Students report'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('dashboard.reports') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Reports') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ __('Students report') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Live breakdown of all enrolled students.') }}</p>
        </div>
        <a href="{{ route('dashboard.reports.export', 'students') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Export CSV') }}</a>
    </div>

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-gray-500">{{ __('Total students') }}</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($total) }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By class') }}</h2>
            <div class="space-y-2">
                @forelse ($byClass as $row)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ $row->class_name ?? '—' }}</span>
                        <span class="text-gray-900">{{ number_format($row->total) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By status') }}</h2>
            <div class="space-y-2">
                @forelse ($byStatus as $row)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ ucfirst($row->status ?? '—') }}</span>
                        <span class="text-gray-900">{{ number_format($row->total) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('By gender') }}</h2>
            <div class="space-y-2">
                @forelse ($byGender as $row)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ ucfirst($row->gender ?? '—') }}</span>
                        <span class="text-gray-900">{{ number_format($row->total) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No data.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
