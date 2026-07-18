@extends('layouts.dashboard')

@section('title', __('Edit class') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit class') }}: {{ $schoolClass->name }}</h1>
        <a href="{{ route('dashboard.classes.show', $schoolClass) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
    </div>
    @include('dashboard.partials.form-errors')
    <form method="post" action="{{ route('dashboard.classes.update', $schoolClass) }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label><input name="name" value="{{ old('name', $schoolClass->name) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Code') }} *</label><input name="code" value="{{ old('code', $schoolClass->code) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Grade level') }} *</label><input name="grade_level" value="{{ old('grade_level', $schoolClass->grade_level) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Academic session') }} *</label>
                <select name="academic_session_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($sessions as $s)
                        <option value="{{ $s->id }}" @selected(old('academic_session_id', $schoolClass->academic_session_id) == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Class teacher') }}</label>
                <select name="class_teacher_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t->id }}" @selected(old('class_teacher_id', $schoolClass->class_teacher_id) == $t->id)>{{ $t->user?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Max students') }}</label><input type="number" name="max_students" value="{{ old('max_students', $schoolClass->max_students) }}" min="1" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Monthly fee') }} *</label><input type="number" step="0.01" name="monthly_fee" value="{{ old('monthly_fee', $schoolClass->monthly_fee) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Admission fee') }} *</label><input type="number" step="0.01" name="admission_fee" value="{{ old('admission_fee', $schoolClass->admission_fee) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Exam fee') }} *</label><input type="number" step="0.01" name="exam_fee" value="{{ old('exam_fee', $schoolClass->exam_fee) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Other fees') }}</label><input type="number" step="0.01" name="other_fees" value="{{ old('other_fees', $schoolClass->other_fees) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div class="flex items-center gap-2 pt-6">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active" class="rounded border-gray-300 text-blue-600" @checked(old('is_active', $schoolClass->is_active))>
                <label for="is_active" class="text-sm font-medium text-gray-700">{{ __('Active') }}</label>
            </div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label><textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description', $schoolClass->description) }}</textarea></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Notes') }}</label><textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('notes', $schoolClass->notes) }}</textarea></div>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
    </form>
@endsection
