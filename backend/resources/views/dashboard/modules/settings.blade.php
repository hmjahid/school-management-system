@extends('layouts.dashboard')

@section('title', __('School settings') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-gray-900">{{ __('School settings') }}</h1>

    <form method="post" action="{{ route('dashboard.settings.update') }}" class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('School name') }}</label>
                <input type="text" name="school_name" value="{{ old('school_name', $settings->school_name) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Tagline') }}</label>
                <input type="text" name="tagline" value="{{ old('tagline', $settings->tagline) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $settings->email) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                <input type="text" name="phone" value="{{ old('phone', $settings->phone) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Address') }}</label>
                <textarea name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('address', $settings->address) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('City') }}</label>
                <input type="text" name="city" value="{{ old('city', $settings->city) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Country') }}</label>
                <input type="text" name="country" value="{{ old('country', $settings->country) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Website URL') }}</label>
                <input type="url" name="website" value="{{ old('website', $settings->website) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta title') }}</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $settings->meta_title) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta description') }}</label>
                <textarea name="meta_description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('meta_description', $settings->meta_description) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Timezone') }}</label>
                <input type="text" name="timezone" value="{{ old('timezone', $settings->timezone) }}" placeholder="UTC"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>
        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Save settings') }}
            </button>
        </div>
    </form>
@endsection
