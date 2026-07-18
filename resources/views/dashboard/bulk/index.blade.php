@extends('layouts.dashboard')

@section('title', __('Bulk import / export'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Bulk import / export') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Move students and teachers in or out via CSV.') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($resources as $key => $label)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __($label) }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ __('Export current data, or upload a CSV to import.') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('dashboard.bulk.export', $key) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Export CSV') }}</a>
                    <a href="{{ route('dashboard.bulk.import', $key) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Import CSV') }}</a>
                </div>
            </div>
        @endforeach
    </div>
@endsection
