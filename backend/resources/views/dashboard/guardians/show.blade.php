@extends('layouts.dashboard')

@section('title', $guardian->user?->name . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $guardian->user?->name }}</h1>
            <p class="text-sm text-gray-600">{{ $guardian->user?->email }} · {{ $guardian->phone }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.parents') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('List') }}</a>
            @can('update', $guardian)
                <a href="{{ route('dashboard.parents.edit', $guardian) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Edit') }}</a>
            @endcan
            @can('delete', $guardian)
                <form method="post" action="{{ route('dashboard.parents.destroy', $guardian) }}" onsubmit="return confirm(@json(__('Remove this guardian?')));">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Remove') }}</button>
                </form>
            @endcan
        </div>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('Linked students') }}</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse ($guardian->students as $st)
                <li><a href="{{ route('dashboard.students.show', $st) }}" class="font-medium text-blue-600 hover:underline">{{ $st->user?->name }}</a> — {{ $st->class?->name ?? '—' }}</li>
            @empty
                <li class="text-gray-500">{{ __('No students linked.') }}</li>
            @endforelse
        </ul>
    </div>
@endsection
