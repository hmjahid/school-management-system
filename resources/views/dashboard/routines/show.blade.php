@extends('layouts.dashboard')
@section('title', __('Routine') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Routine entry') }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.routines.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">{{ __('Back') }}</a>
        @can('update', $routine)
            <a href="{{ route('dashboard.routines.edit', $routine) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Edit') }}</a>
        @endcan
        @can('delete', $routine)
            <form method="post" action="{{ route('dashboard.routines.destroy', $routine) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf @method('delete')
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Delete') }}</button>
            </form>
        @endcan
    </div>
</div>
<div class="grid gap-6 sm:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="space-y-4 text-sm">
            <div><dt class="text-gray-500">{{ __('Day') }}</dt><dd class="font-medium">{{ __(App\Models\Routine::DAYS[$routine->day_of_week] ?? '') }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Time') }}</dt><dd class="font-medium">{{ substr($routine->start_time, 0, 5) }} &ndash; {{ substr($routine->end_time, 0, 5) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Class') }}</dt><dd class="font-medium">{{ $routine->schoolClass?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Section') }}</dt><dd>{{ $routine->section?->name ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Subject') }}</dt><dd class="font-medium">{{ $routine->subject?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Teacher') }}</dt><dd>{{ $routine->teacher?->user?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Room') }}</dt><dd>{{ $routine->room_number ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Batch') }}</dt><dd>{{ $routine->batch?->name ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Academic session') }}</dt><dd>{{ $routine->academicSession?->name ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $routine->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $routine->is_active ? __('Active') : __('Inactive') }}</span></dd></div>
            <div><dt class="text-gray-500">{{ __('Created') }}</dt><dd>{{ $routine->created_at?->format('d M Y H:i') }}</dd></div>
        </dl>
    </div>
</div>
@endsection
