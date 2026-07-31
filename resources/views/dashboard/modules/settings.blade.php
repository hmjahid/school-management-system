@extends('layouts.dashboard')

@section('title', __('School settings') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <h1 class="text-2xl font-bold text-gray-900">{{ __('School settings') }}</h1>
    <p class="mb-6 text-sm text-gray-600">{{ __('Logo, favicon, contact details, and social links shown on the public site and admin area.') }}</p>

    <form method="post" action="{{ route('dashboard.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-3">
        @csrf

        @php
            $sectionVis = $settings->section_visibility ?? [];
            $settingsSections = [
                'general' => ['title' => __('General info'), 'desc' => __('Logo, school name, contact details, social links, and meta.')],
                'sms' => ['title' => __('SMS notifications'), 'desc' => __('Configure the SMS provider and automated messages.')],
                'homepage' => ['title' => __('Homepage sections'), 'desc' => __('Toggle visibility of each section on the public homepage.')],
                'theme' => ['title' => __('Theme customization'), 'desc' => __('Customize colors and font used across the public site.')],
            ];
        @endphp

        {{-- Section: General Info --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-settings-section>
            <button type="button" data-settings-toggle class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">{{ $settingsSections['general']['title'] }}</h2>
                        <p class="text-xs text-gray-500">{{ $settingsSections['general']['desc'] }}</p>
                    </div>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" data-settings-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="border-t border-gray-100 px-5 py-5" data-settings-content>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Site logo (header)') }}</h3>
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
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Favicon (browser tab icon)') }}</h3>
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
                    <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Footer logo (light background)') }}</h3>
                        <p class="mb-2 text-xs text-gray-500">{{ __('Shown on dark footer backgrounds. Recommended: white or light-colored logo.') }}</p>
                        @if($settings->footer_logo_url)
                            <div class="mb-3 flex items-center gap-4">
                                <img src="{{ $settings->footer_logo_url }}" alt="" class="h-14 w-auto max-w-[200px] object-contain rounded bg-gray-800 p-2">
                            </div>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Upload footer logo') }}</label>
                        @include('partials.dashboard.file-button', [
                            'name' => 'footer_logo',
                            'accept' => 'image/*',
                            'id' => 'school_settings_footer_logo',
                            'buttonLabel' => __('Choose image'),
                            'wrapperClass' => '',
                        ])
                        <p class="mt-1 text-xs text-gray-500">{{ __('PNG, JPG, SVG, or WebP. Max 2 MB.') }}</p>
                        @if($settings->footer_logo_path)
                            <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="remove_footer_logo" value="1" @checked(old('remove_footer_logo'))>
                                {{ __('Remove footer logo') }}
                            </label>
                        @endif
                        @error('footer_logo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Footer logo (dark background)') }}</h3>
                        <p class="mb-2 text-xs text-gray-500">{{ __('Shown on light/white footer backgrounds (e.g. when dark mode is off). Recommended: dark-colored logo.') }}</p>
                        @if($settings->footer_logo_dark_url)
                            <div class="mb-3 flex items-center gap-4">
                                <img src="{{ $settings->footer_logo_dark_url }}" alt="" class="h-14 w-auto max-w-[200px] object-contain rounded bg-gray-100 p-2">
                            </div>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Upload dark footer logo') }}</label>
                        @include('partials.dashboard.file-button', [
                            'name' => 'footer_logo_dark',
                            'accept' => 'image/*',
                            'id' => 'school_settings_footer_logo_dark',
                            'buttonLabel' => __('Choose image'),
                            'wrapperClass' => '',
                        ])
                        <p class="mt-1 text-xs text-gray-500">{{ __('PNG, JPG, SVG, or WebP. Max 2 MB.') }}</p>
                        @if($settings->footer_logo_dark_path)
                            <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="remove_footer_logo_dark" value="1" @checked(old('remove_footer_logo_dark'))>
                                {{ __('Remove dark footer logo') }}
                            </label>
                        @endif
                        @error('footer_logo_dark')
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
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Social links') }}</h3>
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
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Academic start month') }}</label>
                        <select name="academic_start_month" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" @selected(old('academic_start_month', $settings->academic_start_month ?? 1) == $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Student ID prefix') }}</label>
                        <input type="text" name="student_id_prefix" value="{{ old('student_id_prefix', $settings->student_id_prefix ?? 'ADM') }}" placeholder="ADM"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: SMS Notifications --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-settings-section>
            <button type="button" data-settings-toggle class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">{{ $settingsSections['sms']['title'] }}</h2>
                        <p class="text-xs text-gray-500">{{ $settingsSections['sms']['desc'] }}</p>
                    </div>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" data-settings-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="hidden border-t border-gray-100 px-5 py-5" data-settings-content>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('SMS driver') }}</label>
                        <select name="sms_driver" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @php $currentDriver = env('SMS_DRIVER', 'log'); @endphp
                            <option value="log" @selected($currentDriver === 'log')>Log (development)</option>
                            <option value="twilio" @selected($currentDriver === 'twilio')>Twilio</option>
                            <option value="nexmo" @selected($currentDriver === 'nexmo')>Nexmo / Vonage</option>
                            <option value="textlocal" @selected($currentDriver === 'textlocal')>TextLocal</option>
                            <option value="africastalking" @selected($currentDriver === 'africastalking')>Africa's Talking</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('"Log" records messages to the Laravel log — useful for testing.') }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Sender ID / From number') }}</label>
                        <input type="text" name="sms_sender_id" value="{{ old('sms_sender_id', $settings->sms_sender_id ?? env('SMS_FROM', '')) }}" maxlength="32" placeholder="SchoolMS"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">{{ __('Alphanumeric sender name or phone number required by your provider.') }}</p>
                    </div>
                    <div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4" id="twilio-fields">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Twilio credentials') }}</h3>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Account SID') }}</label>
                                <input type="text" name="twilio_account_sid" value="{{ env('TWILIO_ACCOUNT_SID', '') }}" placeholder="ACxxxxxxxx"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Auth Token') }}</label>
                                <input type="password" name="twilio_auth_token" value="{{ env('TWILIO_AUTH_TOKEN', '') }}" placeholder="{{ __('Leave unchanged to keep current') }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" autocomplete="new-password">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('From number') }}</label>
                                <input type="text" name="twilio_from_number" value="{{ env('TWILIO_FROM_NUMBER', '') }}" placeholder="+1234567890"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Absence SMS') }}</h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2">
                                <input type="hidden" name="send_absence_sms" value="0">
                                <input type="checkbox" name="send_absence_sms" value="1" @checked(old('send_absence_sms', $settings->send_absence_sms ?? false)) class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-800">{{ __('Send absence SMS to guardians') }}</span>
                            </label>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Absence SMS template') }}</label>
                                <textarea name="absence_sms_template" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('absence_sms_template', $settings->absence_sms_template) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Variables') }}: <code>:name</code>, <code>:date</code>, <code>:class</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Homepage Sections --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-settings-section>
            <button type="button" data-settings-toggle class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">{{ $settingsSections['homepage']['title'] }}</h2>
                        <p class="text-xs text-gray-500">{{ $settingsSections['homepage']['desc'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" data-settings-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div class="hidden border-t border-gray-100 px-5 py-5" data-settings-content>
                @php
                    $vis = $settings->section_visibility ?? [
                        'hero' => true, 'features' => true, 'stats' => true, 'principal' => true,
                        'teachers' => true, 'testimonials' => true, 'remarkable_students' => true,
                        'events' => true, 'news' => true, 'highlights' => true,
                        'cta' => true, 'partners' => true, 'admissions_bar' => true, 'urgent_notices' => true,
                    ];
                    $sectionLabels = [
                        'hero' => 'Hero banner',
                        'features' => 'Features',
                        'stats' => 'Stats counter',
                        'principal' => "Principal's message",
                        'teachers' => 'Teachers section',
                        'testimonials' => 'Testimonials',
                        'remarkable_students' => 'Remarkable students',
                        'events' => 'Upcoming events',
                        'news' => 'Latest news',
                        'highlights' => 'Highlights',
                        'cta' => 'CTA banner',
                        'partners' => 'Partners strip',
                        'admissions_bar' => 'Admissions top bar',
                        'urgent_notices' => 'Urgent notices (hero)',
                    ];
                @endphp
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($sectionLabels as $key => $label)
                        <label class="inline-flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                            <input type="hidden" name="section_visibility[{{ $key }}]" value="0">
                            <input type="checkbox" name="section_visibility[{{ $key }}]" value="1"
                                @checked(old("section_visibility.{$key}", $vis[$key] ?? true))
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section: Other Page Sections --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-settings-section>
            <button type="button" data-settings-toggle class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">{{ __('Other page sections') }}</h2>
                        <p class="text-xs text-gray-500">{{ __('Toggle visibility of sections on Admissions, Contact, Faculty, Gallery, News, and Payments pages.') }}</p>
                    </div>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" data-settings-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="hidden border-t border-gray-100 px-5 py-5" data-settings-content>
                @php
                    $otherPageSections = [
                        'Admissions' => [
                            'adm_hero' => 'Hero banner',
                            'adm_process' => 'Admission process',
                            'adm_fee' => 'Fee structure',
                            'adm_prospectus' => 'Download prospectus',
                            'adm_faq' => 'FAQs',
                            'adm_cta' => 'CTA banner',
                            'adm_scholarship' => 'Scholarship form',
                        ],
                        'Contact' => [
                            'contact_hero' => 'Hero banner',
                            'contact_cards' => 'Contact cards',
                            'contact_form' => 'Contact form',
                            'contact_hours' => 'Opening hours',
                            'contact_emergency' => 'Emergency contacts',
                            'contact_map' => 'Map',
                            'contact_faq' => 'FAQs',
                        ],
                        'Faculty' => [
                            'faculty_hero' => 'Hero banner',
                            'faculty_search' => 'Search & filter',
                            'faculty_grid' => 'Faculty grid',
                        ],
                        'Gallery' => [
                            'gallery_hero' => 'Hero banner',
                            'gallery_tabs' => 'Category tabs',
                            'gallery_grid' => 'Gallery grid',
                        ],
                        'News' => [
                            'news_hero' => 'Hero banner',
                            'news_featured' => 'Featured article',
                            'news_grid' => 'News grid',
                        ],
                        'Payments' => [
                            'payments_hero' => 'Hero banner',
                            'payments_fee' => 'Fee table',
                            'payments_gateways' => 'Payment gateways',
                        ],
                        'About / Pages' => [
                            'page_hero' => 'Hero banner',
                            'page_content' => 'Page content',
                        ],
                        'Events' => [
                            'events_hero' => 'Hero banner',
                            'events_filters' => 'Filters & view toggle',
                            'events_upcoming' => 'Upcoming events',
                            'events_past' => 'Past events',
                        ],
                        'Notices' => [
                            'notices_hero' => 'Hero banner',
                            'notices_list' => 'Notices list',
                        ],
                        'Results' => [
                            'results_hero' => 'Hero banner',
                            'results_form' => 'Search form',
                        ],
                        'Routines' => [
                            'routines_hero' => 'Hero banner',
                            'routines_filter' => 'Filter form',
                            'routines_grid' => 'Routine grid',
                        ],
                        'Transport' => [
                            'transport_hero' => 'Hero banner',
                            'transport_routes' => 'Route cards',
                            'transport_fleet' => 'Fleet section',
                            'transport_map' => 'Route map',
                        ],
                    ];
                @endphp
                <div class="space-y-5">
                    @foreach($otherPageSections as $pageTitle => $sections)
                        <div>
                            <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">{{ $pageTitle }}</h3>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($sections as $key => $label)
                                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                                        <input type="hidden" name="section_visibility[{{ $key }}]" value="0">
                                        <input type="checkbox" name="section_visibility[{{ $key }}]" value="1"
                                            @checked(old("section_visibility.{$key}", $vis[$key] ?? true))
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section: Theme Customization --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-settings-section>
            <button type="button" data-settings-toggle class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">{{ $settingsSections['theme']['title'] }}</h2>
                        <p class="text-xs text-gray-500">{{ $settingsSections['theme']['desc'] }}</p>
                    </div>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" data-settings-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="hidden border-t border-gray-100 px-5 py-5" data-settings-content>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Primary color') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" data-color-preview value="{{ old('theme_primary_color', $settings->theme_primary_color ?? '#2563eb') }}" class="h-10 w-14 cursor-pointer rounded border border-gray-300">
                            <input type="text" name="theme_primary_color" value="{{ old('theme_primary_color', $settings->theme_primary_color ?? '') }}" placeholder="#2563eb" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Secondary color') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" data-color-preview value="{{ old('theme_secondary_color', $settings->theme_secondary_color ?? '#f97316') }}" class="h-10 w-14 cursor-pointer rounded border border-gray-300">
                            <input type="text" name="theme_secondary_color" value="{{ old('theme_secondary_color', $settings->theme_secondary_color ?? '') }}" placeholder="#f97316" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Font family') }}</label>
                        <select name="theme_font_family" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === '')>{{ __('Default (Inter)') }}</option>
                            <option value="Inter, sans-serif" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === 'Inter, sans-serif')">Inter</option>
                            <option value="Roboto, sans-serif" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === 'Roboto, sans-serif')">Roboto</option>
                            <option value="Poppins, sans-serif" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === 'Poppins, sans-serif')">Poppins</option>
                            <option value="Open Sans, sans-serif" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === 'Open Sans, sans-serif')">Open Sans</option>
                            <option value="Lato, sans-serif" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === 'Lato, sans-serif')">Lato</option>
                            <option value="Montserrat, sans-serif" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === 'Montserrat, sans-serif')">Montserrat</option>
                            <option value="Georgia, serif" @selected(old('theme_font_family', $settings->theme_font_family ?? '') === 'Georgia, serif')">Georgia</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Border radius') }}</label>
                        <select name="theme_border_radius" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '')>{{ __('Default (rounded)') }}</option>
                            <option value="0" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '0')>{{ __('None (square)') }}</option>
                            <option value="0.25rem" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '0.25rem')>{{ __('Small') }}</option>
                            <option value="0.5rem" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '0.5rem')>{{ __('Medium') }}</option>
                            <option value="0.75rem" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '0.75rem')>{{ __('Large') }}</option>
                            <option value="1rem" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '1rem')>{{ __('Extra large') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Save settings') }}
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Accordion toggle
            document.querySelectorAll('[data-settings-toggle]').forEach(function(btn) {
                var section = btn.closest('[data-settings-section]');
                var content = section.querySelector('[data-settings-content]');
                var chevron = section.querySelector('[data-settings-chevron]');
                btn.addEventListener('click', function() {
                    var isOpen = !content.classList.contains('hidden');
                    // Close all others
                    document.querySelectorAll('[data-settings-section]').forEach(function(s) {
                        var c = s.querySelector('[data-settings-content]');
                        var ch = s.querySelector('[data-settings-chevron]');
                        if (c !== content) {
                            c.classList.add('hidden');
                            if (ch) ch.style.transform = '';
                        }
                    });
                    if (!isOpen) {
                        content.classList.remove('hidden');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    } else {
                        if (chevron) chevron.style.transform = '';
                    }
                });
            });

            // SMS driver toggle
            var sel = document.querySelector('select[name="sms_driver"]');
            var twilio = document.getElementById('twilio-fields');
            if (sel && twilio) {
                function toggle() { twilio.classList.toggle('hidden', sel.value !== 'twilio'); }
                sel.addEventListener('change', toggle);
                toggle();
            }

            // Color preview sync
            document.querySelectorAll('input[data-color-preview]').forEach(function(colorInput) {
                var textInput = colorInput.parentElement.querySelector('input[type="text"]');
                if (!textInput) return;
                textInput.addEventListener('input', function() {
                    if (/^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
                        colorInput.value = textInput.value;
                    }
                });
                colorInput.addEventListener('input', function() {
                    textInput.value = colorInput.value;
                });
            });
        });
    </script>
@endsection
