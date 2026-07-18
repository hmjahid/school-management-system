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
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('School name') }} <span class="text-gray-400">(EN)</span></label>
                <input type="text" name="school_name" value="{{ old('school_name', $settings->school_name) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('School name') }} <span class="text-gray-400">(বাংলা)</span></label>
                <input type="text" name="school_name_bn" value="{{ old('school_name_bn', $settings->school_name_bn) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @error('school_name_bn')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Tagline') }} <span class="text-gray-400">(EN)</span></label>
                <input type="text" name="tagline" value="{{ old('tagline', $settings->tagline) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Tagline') }} <span class="text-gray-400">(বাংলা)</span></label>
                <input type="text" name="tagline_bn" value="{{ old('tagline_bn', $settings->tagline_bn) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @error('tagline_bn')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
                <p class="mb-3 text-xs text-gray-500">{{ __('Add a URL and enable the toggle to show each platform\'s icon in the site header and footer.') }}</p>
                <div class="space-y-3">
                    @php
                        $socials = [
                            ['label' => 'Facebook', 'urlField' => 'facebook_url', 'showField' => 'show_facebook', 'icon' => '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>'],
                            ['label' => 'Instagram', 'urlField' => 'instagram_url', 'showField' => 'show_instagram', 'icon' => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>'],
                            ['label' => 'X (Twitter)', 'urlField' => 'twitter_url', 'showField' => 'show_twitter', 'icon' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>'],
                            ['label' => 'YouTube', 'urlField' => 'youtube_url', 'showField' => 'show_youtube', 'icon' => '<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>'],
                            ['label' => 'LinkedIn', 'urlField' => 'linkedin_url', 'showField' => 'show_linkedin', 'icon' => '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>'],
                        ];
                    @endphp
                    @foreach ($socials as $soc)
                        <div class="grid gap-2 rounded-md border border-gray-200 bg-white p-3 sm:grid-cols-[auto_1fr_auto] sm:items-center">
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-gray-100 text-gray-600">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $soc['icon'] !!}</svg>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ $soc['label'] }}</label>
                                <input type="url" name="{{ $soc['urlField'] }}" value="{{ old($soc['urlField'], $settings->{$soc['urlField']}) }}" placeholder="https://"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-center gap-2 sm:justify-end">
                                <input type="hidden" name="{{ $soc['showField'] }}" value="0">
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="{{ $soc['showField'] }}" value="1" @checked(old($soc['showField'], $settings->{$soc['showField']}))
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="select-none">{{ __('Show icon') }}</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
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
            <div>
                <label for="default_locale" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Default site language') }}</label>
                <select id="default_locale" name="default_locale"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="en" @selected(old('default_locale', $settings->default_locale) === 'en')>English</option>
                    <option value="bn" @selected(old('default_locale', $settings->default_locale) === 'bn')>বাংলা (Bengali)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">{{ __('The language first-time visitors see. They can still switch using the language toggle.') }}</p>
                @error('default_locale')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-8 border-t border-gray-100 pt-6">
            <h2 class="text-base font-semibold text-gray-900">{{ __('SMS notifications') }}</h2>
            <p class="mt-1 text-xs text-gray-500">{{ __('Configure automated SMS messages sent by the system.') }}</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                    <input type="hidden" name="send_absence_sms" value="0">
                    <input type="checkbox" name="send_absence_sms" value="1" @checked(old('send_absence_sms', $settings->send_absence_sms ?? false)) class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-800">{{ __('Send absence SMS to guardians') }}</span>
                </label>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Sender ID') }}</label>
                    <input type="text" name="sms_sender_id" value="{{ old('sms_sender_id', $settings->sms_sender_id) }}" maxlength="32" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Absence SMS template') }}</label>
                    <textarea name="absence_sms_template" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('absence_sms_template', $settings->absence_sms_template) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Variables') }}: <code>:name</code>, <code>:date</code>, <code>:class</code></p>
                </div>
            </div>
        </div>
        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Save settings') }}
            </button>
        </div>
    </form>
@endsection
