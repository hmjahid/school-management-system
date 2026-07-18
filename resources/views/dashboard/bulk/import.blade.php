@extends('layouts.dashboard')

@section('title', __('Import :res', ['res' => __($label)]))

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.bulk') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Bulk import / export') }}</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ __('Import :res', ['res' => __($label)]) }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Upload a UTF-8 CSV with the headers shown below. Existing rows are updated by their natural key.') }}</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if (session('import_errors'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-medium">{{ __('Per-row errors:') }}</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach (session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('dashboard.partials.form-errors')

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="post" action="{{ route('dashboard.bulk.import.store', $resource) }}" enctype="multipart/form-data" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('CSV file') }} *</label>
                <input name="file" type="file" accept=".csv,text/csv" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">{{ __('Max 5 MB. UTF-8 encoded.') }}</p>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <input id="dry_run" name="dry_run" type="checkbox" value="1" class="size-4 rounded border-gray-300 text-blue-600">
                <label for="dry_run" class="text-sm text-gray-700">{{ __('Dry run (parse only, do not save)') }}</label>
            </div>
            <div class="mt-6">
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Upload and import') }}</button>
            </div>
        </form>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Required columns') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ __('Header names below — order does not matter.') }}</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach ($headers as $h)
                                <th class="px-3 py-2 text-left font-mono font-semibold text-gray-700">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>

            <h2 class="mt-6 text-lg font-semibold text-gray-900">{{ __('Sample row') }}</h2>
            <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs">
                @foreach ($sample as $row)
                    <div>{{ implode(', ', $row) }}</div>
                @endforeach
            </div>

            <a href="{{ route('dashboard.bulk.export', $resource) }}" class="mt-6 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Download existing data as a starting template') }} →</a>
        </div>
    </div>
@endsection
