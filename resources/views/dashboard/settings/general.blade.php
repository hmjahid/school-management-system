@extends('layouts.dashboard')

@section('title', __('General settings') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('School Info') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('School name, logos, contact details, social links, and meta.') }}</p>
    </div>

    <form method="post" action="{{ route('dashboard.settings.update.general') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
        @csrf
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('General info') }}</h2>
            <p class="mb-5 text-sm text-gray-500">{{ __('School name, contact details, social links, and meta.') }}</p>

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
                        'id' => 'settings_logo',
                        'buttonLabel' => __('Choose image'),
                        'wrapperClass' => '',
                    ])
                    <p class="mt-1 text-xs text-gray-500">{{ __('PNG, JPG, or WebP. Max 2 MB. Replaces the current logo.') }}</p>
                    @if($settings->logo_path)
                        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_logo" value="1">
                            {{ __('Remove current logo') }}
                        </label>
                    @endif
                </div>

                <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Favicon') }}</h3>
                    @if($settings->favicon_url)
                        <div class="mb-3 flex items-center gap-4">
                            <img src="{{ $settings->favicon_url }}" alt="" width="32" height="32" class="size-8 rounded border border-gray-200 bg-white object-contain p-0.5">
                        </div>
                    @endif
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Upload favicon') }}</label>
                    @include('partials.dashboard.file-button', [
                        'name' => 'favicon',
                        'accept' => '.ico,.png,.jpg,.jpeg,.gif,.webp,.svg,image/*',
                        'id' => 'settings_favicon',
                        'buttonLabel' => __('Choose favicon file'),
                        'wrapperClass' => '',
                    ])
                    <p class="mt-1 text-xs text-gray-500">{{ __('ICO, PNG, SVG, or WebP. Square image, max 512 KB.') }}</p>
                    @if($settings->favicon_path)
                        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_favicon" value="1">
                            {{ __('Remove current favicon') }}
                        </label>
                    @endif
                </div>

                <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Footer logo (light background)') }}</h3>
                    @if($settings->footer_logo_url)
                        <div class="mb-3 flex items-center gap-4">
                            <img src="{{ $settings->footer_logo_url }}" alt="" class="h-14 w-auto max-w-[200px] object-contain rounded bg-gray-800 p-2">
                        </div>
                    @endif
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Upload footer logo') }}</label>
                    @include('partials.dashboard.file-button', [
                        'name' => 'footer_logo',
                        'accept' => 'image/*',
                        'id' => 'settings_footer_logo',
                        'buttonLabel' => __('Choose image'),
                        'wrapperClass' => '',
                    ])
                    <p class="mt-1 text-xs text-gray-500">{{ __('PNG, JPG, SVG, or WebP. Max 2 MB.') }}</p>
                    @if($settings->footer_logo_path)
                        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_footer_logo" value="1">
                            {{ __('Remove footer logo') }}
                        </label>
                    @endif
                </div>

                <div class="sm:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Footer logo (dark background)') }}</h3>
                    @if($settings->footer_logo_dark_url)
                        <div class="mb-3 flex items-center gap-4">
                            <img src="{{ $settings->footer_logo_dark_url }}" alt="" class="h-14 w-auto max-w-[200px] object-contain rounded bg-gray-100 p-2">
                        </div>
                    @endif
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Upload dark footer logo') }}</label>
                    @include('partials.dashboard.file-button', [
                        'name' => 'footer_logo_dark',
                        'accept' => 'image/*',
                        'id' => 'settings_footer_logo_dark',
                        'buttonLabel' => __('Choose image'),
                        'wrapperClass' => '',
                    ])
                    <p class="mt-1 text-xs text-gray-500">{{ __('PNG, JPG, SVG, or WebP. Max 2 MB.') }}</p>
                    @if($settings->footer_logo_dark_path)
                        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_footer_logo_dark" value="1">
                            {{ __('Remove dark footer logo') }}
                        </label>
                    @endif
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
                    <label for="default_locale" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Default site language') }}</label>
                    <select id="default_locale" name="default_locale"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="en" @selected(old('default_locale', $settings->default_locale) === 'en')>English</option>
                        <option value="bn" @selected(old('default_locale', $settings->default_locale) === 'bn')>বাংলা (Bengali)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">{{ __('The language first-time visitors see.') }}</p>
                </div>
            </div>

            {{-- Social Links --}}
            <div class="mt-6 rounded-lg border border-gray-100 bg-gray-50 p-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Social links') }}</h3>
                <p class="mb-3 text-xs text-gray-500">{{ __('Add a URL and enable the toggle to show each platform\'s icon.') }}</p>
                <div class="space-y-3">
                    @php
                        $socials = [
                            ['label' => 'Facebook', 'urlField' => 'facebook_url', 'showField' => 'show_facebook'],
                            ['label' => 'Instagram', 'urlField' => 'instagram_url', 'showField' => 'show_instagram'],
                            ['label' => 'X (Twitter)', 'urlField' => 'twitter_url', 'showField' => 'show_twitter'],
                            ['label' => 'YouTube', 'urlField' => 'youtube_url', 'showField' => 'show_youtube'],
                            ['label' => 'LinkedIn', 'urlField' => 'linkedin_url', 'showField' => 'show_linkedin'],
                        ];
                    @endphp
                    @foreach ($socials as $soc)
                        <div class="grid gap-2 rounded-md border border-gray-200 bg-white p-3 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">{{ $soc['label'] }}</label>
                                <input type="url" name="{{ $soc['urlField'] }}" value="{{ old($soc['urlField'], $settings->{$soc['urlField']}) }}" placeholder="https://"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-center gap-2">
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

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta title') }}</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $settings->meta_title) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta description') }}</label>
                    <textarea name="meta_description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('meta_description', $settings->meta_description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Save settings') }}
            </button>
        </div>
    </form>
@endsection
