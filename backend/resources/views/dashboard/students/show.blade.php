@extends('layouts.dashboard')

@section('title', $student->user?->name . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $student->user?->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $student->user?->email }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.students') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('List') }}</a>
            @can('update', $student)
                <a href="{{ route('dashboard.students.edit', $student) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Edit') }}</a>
            @endcan
            @can('delete', $student)
                <form method="post" action="{{ route('dashboard.students.destroy', $student) }}" onsubmit="return confirm(@json(__('Remove this student?')));">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Remove') }}</button>
                </form>
            @endcan
        </div>
    </div>

    <dl class="grid gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:grid-cols-2">
        <div><dt class="text-sm text-gray-500">{{ __('Admission') }}</dt><dd class="font-medium text-gray-900">{{ $student->admission_number ?? $student->admission_no ?? '—' }}</dd></div>
        <div><dt class="text-sm text-gray-500">{{ __('Class') }}</dt><dd class="font-medium text-gray-900">{{ $student->class?->name ?? '—' }}</dd></div>
        <div><dt class="text-sm text-gray-500">{{ __('Section') }}</dt><dd class="font-medium text-gray-900">{{ $student->section?->name ?? '—' }}</dd></div>
        <div><dt class="text-sm text-gray-500">{{ __('Status') }}</dt><dd class="font-medium text-gray-900">{{ $student->status ?? '—' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-sm text-gray-500">{{ __('Address') }}</dt><dd class="text-gray-900">{{ $student->present_address ?? '—' }}</dd></div>
    </dl>
@endsection
