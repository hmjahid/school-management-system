@extends('layouts.dashboard')
@section('title', __('Edit assignment') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit assignment') }}</h1>
    <a href="{{ route('dashboard.assignments.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.assignments.update', $assignment) }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf @method('put')
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Title') }} *</label><input name="title" value="{{ old('title', $assignment->title) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Subject') }} *</label><select name="subject_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($subjects as $s)<option value="{{ $s->id }}" @selected($assignment->subject_id == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Batch') }} *</label><select name="batch_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($batches as $b)<option value="{{ $b->id }}" @selected($assignment->batch_id == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Class') }}</label><select name="class_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">{{ __('— Select —') }}</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($assignment->class_id == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Section') }}</label><select name="section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">{{ __('— Select —') }}</option></select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Due date') }} *</label><input type="datetime-local" name="due_date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Total marks') }}</label><input type="number" name="total_marks" value="{{ old('total_marks', $assignment->total_marks) }}" min="0" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Attachment') }}</label><input type="file" name="file" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@if($assignment->file_path)<p class="mt-1 text-xs text-gray-500">{{ __('Current file:') }} {{ $assignment->file_path }}</p>@endif</div>
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="allow_guardian_notes" id="allow_guardian_notes" value="1" {{ old('allow_guardian_notes', $assignment->allow_guardian_notes) ? 'checked' : '' }} class="rounded border-gray-300">
        <label for="allow_guardian_notes" class="text-sm font-medium text-gray-700">{{ __('Allow guardian notes') }}</label>
    </div>
    <div><label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label><textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description', $assignment->description) }}</textarea></div>
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Update') }}</button>
</form>
@endsection