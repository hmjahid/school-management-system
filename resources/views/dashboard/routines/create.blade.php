@extends('layouts.dashboard')
@section('title', __('Add routine entry') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Add routine entry') }}</h1>
    <a href="{{ route('dashboard.routines.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.routines.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Class') }} *</label><select name="school_class_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Section') }}</label><select name="section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">{{ __('None') }}</option>@foreach($sections as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Subject') }} *</label><select name="subject_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Teacher') }} *</label><select name="teacher_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->user?->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Batch') }}</label><select name="batch_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">{{ __('None') }}</option>@foreach($batches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Session') }}</label><select name="academic_session_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">{{ __('None') }}</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Day') }} *</label><select name="day_of_week" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($days as $k => $d)<option value="{{ $k }}">{{ __($d) }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Start time') }} *</label><input type="time" name="start_time" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('End time') }} *</label><input type="time" name="end_time" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Room number') }}</label><input name="room_number" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div class="flex items-center gap-2 pt-6">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label class="text-sm font-medium text-gray-700">{{ __('Active') }}</label>
        </div>
    </div>
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
</form>
@endsection