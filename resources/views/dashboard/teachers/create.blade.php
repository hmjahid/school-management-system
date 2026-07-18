@extends('layouts.dashboard')

@section('title', __('Add teacher') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Add teacher') }}</h1>
        <a href="{{ route('dashboard.teachers') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>
    @include('dashboard.partials.form-errors')
    <form method="post" action="{{ route('dashboard.teachers.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label><input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label><input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Password') }} *</label><input type="password" name="password" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Confirm password') }} *</label><input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Employee ID') }} *</label><input name="employee_id" value="{{ old('employee_id') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Gender') }} *</label>
                <select name="gender" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['male' => __('Male'), 'female' => __('Female'), 'other' => __('Other')] as $v => $l)
                        <option value="{{ $v }}" @selected(old('gender') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Phone') }} *</label><input name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Joining date') }} *</label><input type="date" name="joining_date" value="{{ old('joining_date') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['active', 'inactive', 'on_leave', 'retired'] as $st)
                        <option value="{{ $st }}" @selected(old('status', 'active') === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Salary') }} *</label><input type="number" step="0.01" name="salary" value="{{ old('salary', '0') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Salary type') }} *</label>
                <select name="salary_type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['monthly', 'hourly', 'daily', 'weekly'] as $t)
                        <option value="{{ $t }}" @selected(old('salary_type', 'monthly') === $t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Present address') }} *</label><textarea name="present_address" rows="2" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('present_address') }}</textarea></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Subjects taught') }}</label>
            <select name="subjects[]" multiple class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" size="5">
                @foreach ($subjects as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">{{ __('Hold Ctrl/Cmd to select multiple.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
    </form>
@endsection
