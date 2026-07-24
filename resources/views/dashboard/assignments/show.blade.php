@extends('layouts.dashboard')
@section('title', $assignment->title . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ $assignment->title }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.assignments.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">{{ __('Back') }}</a>
        <a href="{{ route('dashboard.assignments.submissions', $assignment) }}" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ __('Submissions') }}</a>
        @can('update', $assignment)<a href="{{ route('dashboard.assignments.edit', $assignment) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Edit') }}</a>@endcan
        @can('delete', $assignment)
            <form method="post" action="{{ route('dashboard.assignments.destroy', $assignment) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf @method('delete')
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Delete') }}</button>
            </form>
        @endcan
    </div>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="prose prose-sm max-w-none">{{ nl2br(e($assignment->description ?? __('No description.'))) }}</div>
        @if($assignment->file_path)<a href="{{ Storage::url($assignment->file_path) }}" target="_blank" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">{{ __('Download attachment') }}</a>@endif
    </div>
    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"><dl class="space-y-3 text-sm"><div><dt class="text-gray-500">{{ __('Subject') }}</dt><dd class="font-medium">{{ $assignment->subject?->name }}</dd></div><div><dt class="text-gray-500">{{ __('Batch') }}</dt><dd class="font-medium">{{ $assignment->batch?->name }}</dd></div><div><dt class="text-gray-500">{{ __('Due date') }}</dt><dd class="font-medium">{{ $assignment->due_date?->format('d M Y H:i') }}</dd></div><div><dt class="text-gray-500">{{ __('Total marks') }}</dt><dd class="font-medium">{{ $assignment->total_marks ?? 'N/A' }}</dd></div><div><dt class="text-gray-500">{{ __('Submissions') }}</dt><dd class="font-medium">{{ $assignment->submissions->count() }}</dd></div></dl></div>
    </div>
</div>
@endsection