@extends('layouts.dashboard')

@section('title', __('School settings') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <h1 class="text-2xl font-bold text-gray-900">{{ __('School settings') }}</h1>
    <p class="mb-6 text-sm text-gray-600">{{ __('Logo, favicon, contact details, and social links shown on the public site and admin area.') }}</p>

    <form method="post" action="{{ route('dashboard.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                <h2 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Site logo (header)') }}</h2>
                @if($settings->logo_url)
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ $settings->logo_url }}" alt="" class="h-14 w-auto max-w-[200px] object-contain">
                    </div>
                @endif
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Upload logo') }}</label>
                @include('partials.dashboard.file-button', [
                    'name' => 'logo',
                    'accept' => 'image/*',
                    'id' => 'school_settings_logo',
                    'buttonLabel' => __('Choose image'),
                    'wrapperClass' => '',
                ])
                <p class="mt-1 text-xs text-gray-500">{{ __('PNG, JPG, or WebP. Max 2 MB. Replaces the current logo.') }}</p>
                @if($settings->logo_path)
                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                        {{ __('Remove current logo') }}
                    </label>
                @endif
                @error('logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                <h2 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Favicon (browser tab icon)') }}</h2>
                @if($settings->favicon_url)
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ $settings->favicon_url }}" alt="" width="32" height="32" class="size-8 rounded border border-gray-200 bg-white object-contain p-0.5">
                    </div>
                @endif
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Upload favicon') }}</label>
                @include('partials.dashboard.file-button', [
                    'name' => 'favicon',
                    'accept' => '.ico,.png,.jpg,.jpeg,.gif,.webp,.svg,image/*',
                    'id' => 'school_settings_favicon',
                    'buttonLabel' => __('Choose favicon file'),
                    'wrapperClass' => '',
                ])
                <p class="mt-1 text-xs text-gray-500">{{ __('ICO, PNG, SVG, or WebP recommended. Square image, max 512 KB.') }}</p>
                @if($settings->favicon_path)
                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="remove_favicon" value="1" @checked(old('remove_favicon'))>
                        {{ __('Remove current favicon') }}
                    </label>
                @endif
                @error('favicon')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
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
            <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                <h2 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Social links') }}</h2>
                <p class="mb-3 text-xs text-gray-500">{{ __('Shown in the site header and footer when set.') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Facebook</label>
                        <input type="url" name="facebook_url" value="{{ old('facebook_url', $settings->facebook_url) }}" placeholder="https://"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Instagram</label>
                        <input type="url" name="instagram_url" value="{{ old('instagram_url', $settings->instagram_url) }}" placeholder="https://"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">X (Twitter)</label>
                        <input type="url" name="twitter_url" value="{{ old('twitter_url', $settings->twitter_url) }}" placeholder="https://"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">YouTube</label>
                        <input type="url" name="youtube_url" value="{{ old('youtube_url', $settings->youtube_url) }}" placeholder="https://"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">LinkedIn</label>
                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $settings->linkedin_url) }}" placeholder="https://"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
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
