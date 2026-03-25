@extends('layouts.dashboard')

@section('title', $schoolClass->name . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $schoolClass->name }}</h1>
            <p class="text-sm text-gray-600">{{ $schoolClass->code }} · {{ __('Grade') }} {{ $schoolClass->grade_level }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.classes') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('List') }}</a>
            @can('update', $schoolClass)
                <a href="{{ route('dashboard.classes.edit', $schoolClass) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Edit') }}</a>
            @endcan
            @can('delete', $schoolClass)
                <form method="post" action="{{ route('dashboard.classes.destroy', $schoolClass) }}" onsubmit="return confirm(@json(__('Remove this class?')));">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Remove') }}</button>
                </form>
            @endcan
        </div>
    </div>
    <dl class="grid gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:grid-cols-2">
        <div><dt class="text-sm text-gray-500">{{ __('Session') }}</dt><dd class="font-medium">{{ $schoolClass->academicSession?->name ?? '—' }}</dd></div>
        <div><dt class="text-sm text-gray-500">{{ __('Class teacher') }}</dt><dd class="font-medium">{{ $schoolClass->classTeacher?->user?->name ?? '—' }}</dd></div>
        <div><dt class="text-sm text-gray-500">{{ __('Monthly fee') }}</dt><dd>{{ $schoolClass->monthly_fee ?? '—' }}</dd></div>
        <div><dt class="text-sm text-gray-500">{{ __('Sections') }}</dt><dd>{{ $schoolClass->sections?->pluck('name')->join(', ') ?: '—' }}</dd></div>
    </dl>
@endsection
