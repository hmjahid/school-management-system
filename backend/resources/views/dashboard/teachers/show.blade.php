@extends('layouts.dashboard')

@section('title', $teacher->user?->name . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $teacher->user?->name }}</h1>
            <p class="text-sm text-gray-600">{{ $teacher->user?->email }} · {{ $teacher->employee_id }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.teachers') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('List') }}</a>
            @can('update', $teacher)
                <a href="{{ route('dashboard.teachers.edit', $teacher) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Edit') }}</a>
            @endcan
            @can('delete', $teacher)
                <form method="post" action="{{ route('dashboard.teachers.destroy', $teacher) }}" onsubmit="return confirm(@json(__('Remove this teacher?')));">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Remove') }}</button>
                </form>
            @endcan
        </div>
    </div>
    <dl class="grid gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:grid-cols-2">
        <div><dt class="text-sm text-gray-500">{{ __('Phone') }}</dt><dd class="font-medium">{{ $teacher->phone ?? '—' }}</dd></div>
        <div><dt class="text-sm text-gray-500">{{ __('Status') }}</dt><dd class="font-medium">{{ $teacher->status ?? '—' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-sm text-gray-500">{{ __('Subjects') }}</dt><dd>{{ $teacher->subjects->pluck('name')->join(', ') ?: '—' }}</dd></div>
    </dl>
@endsection
