@extends('layouts.dashboard')

@section('title', __('New event'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.events') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Events') }}</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ __('New event') }}</h1>
    </div>

    @include('dashboard.partials.form-errors')

    <form method="post" action="{{ route('dashboard.events.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('Title') }} *</label>
                <input name="title" value="{{ old('title') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Start') }} *</label>
                <input name="start_date" type="datetime-local" value="{{ old('start_date') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('End') }}</label>
                <input name="end_date" type="datetime-local" value="{{ old('end_date') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Location') }}</label>
                <input name="location" value="{{ old('location') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['draft', 'published', 'cancelled', 'completed'] as $s)
                        <option value="{{ $s }}" @selected(old('status', 'published') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Max attendees') }}</label>
                <input name="max_attendees" type="number" min="1" value="{{ old('max_attendees') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Registration deadline') }}</label>
                <input name="registration_deadline" type="datetime-local" value="{{ old('registration_deadline') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('Meeting URL (if virtual)') }}</label>
                <input name="meeting_url" type="url" value="{{ old('meeting_url') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2 flex items-center gap-2">
                <input id="is_virtual" name="is_virtual" type="checkbox" value="1" {{ old('is_virtual') ? 'checked' : '' }} class="size-4 rounded border-gray-300 text-blue-600">
                <label for="is_virtual" class="text-sm text-gray-700">{{ __('This is a virtual event') }}</label>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description') }}</textarea>
            </div>
        </div>
        <div>
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Create event') }}</button>
        </div>
    </form>
@endsection
