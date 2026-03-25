@extends('layouts.dashboard')

@section('title', __('Edit student') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit student') }}</h1>
        <a href="{{ route('dashboard.students.show', $student) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('View profile') }}</a>
    </div>

    @include('dashboard.partials.form-errors')

    <form method="post" action="{{ route('dashboard.students.update', $student) }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Full name') }} *</label>
                <input name="name" value="{{ old('name', $student->user?->name) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label>
                <input name="email" type="email" value="{{ old('email', $student->user?->email) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('New password') }}</label>
                <input name="password" type="password" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" autocomplete="new-password" placeholder="{{ __('Leave blank to keep current') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Confirm new password') }}</label>
                <input name="password_confirmation" type="password" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" autocomplete="new-password">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Admission number') }} *</label>
                <input name="admission_number" value="{{ old('admission_number', $student->admission_number ?? $student->admission_no) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Admission date') }} *</label>
                <input name="admission_date" type="date" value="{{ old('admission_date', $student->admission_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Class') }} *</label>
                <select name="class_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected(old('class_id', $student->class_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Section') }}</label>
                <select name="section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($sections as $s)
                        <option value="{{ $s->id }}" @selected(old('section_id', $student->section_id) == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Batch') }}</label>
                <select name="batch_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($batches as $b)
                        <option value="{{ $b->id }}" @selected(old('batch_id', $student->batch_id) == $b->id)>{{ $b->name ?? $b->code ?? ('#'.$b->id) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Guardian') }}</label>
                <select name="guardian_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($guardians as $g)
                        <option value="{{ $g->id }}" @selected(old('guardian_id', $student->guardian_id) == $g->id)>{{ $g->user?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Roll number') }}</label>
                <input name="roll_number" value="{{ old('roll_number', $student->roll_number ?? $student->roll_no) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['active', 'inactive', 'graduated', 'transferred'] as $val)
                        <option value="{{ $val }}" @selected(old('status', $student->status) === $val)>{{ ucfirst($val) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Present address') }} *</label>
            <textarea name="present_address" rows="2" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('present_address', $student->present_address) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Permanent address') }}</label>
            <textarea name="permanent_address" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('permanent_address', $student->permanent_address) }}</textarea>
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save changes') }}</button>
    </form>
@endsection
