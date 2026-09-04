@extends('layouts.dashboard')

@section('title', __('School settings') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $tab = request()->query('tab', 'theme');
        $sectionVis = $settings->section_visibility ?? [];
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Settings') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Theme, localization, payment gateways, and library rules.') }}</p>
    </div>

    {{-- Tab Navigation --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-x-6 gap-y-2 text-sm font-medium" id="settings-tabs">
            <button type="button" data-tab="theme" class="tab-link whitespace-nowrap border-b-2 px-1 pb-3 {{ $tab === 'theme' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ __('dashboard.tab_theme') ?? __('Theme') }}
            </button>
            <button type="button" data-tab="localization" class="tab-link whitespace-nowrap border-b-2 px-1 pb-3 {{ $tab === 'localization' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ __('dashboard.tab_localization') ?? __('Localization') }}
            </button>
            <button type="button" data-tab="payment" class="tab-link whitespace-nowrap border-b-2 px-1 pb-3 {{ $tab === 'payment' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ __('dashboard.tab_payment') ?? __('Payment') }}
            </button>
            <button type="button" data-tab="library" class="tab-link whitespace-nowrap border-b-2 px-1 pb-3 {{ $tab === 'library' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ __('dashboard.tab_library') ?? __('Library') }}
            </button>
            <button type="button" data-tab="academic" class="tab-link whitespace-nowrap border-b-2 px-1 pb-3 {{ $tab === 'academic' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ __('Academic') }}
            </button>
            <button type="button" data-tab="sms" class="tab-link whitespace-nowrap border-b-2 px-1 pb-3 {{ $tab === 'sms' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ __('SMS') }}
            </button>
            <button type="button" data-tab="mail" class="tab-link whitespace-nowrap border-b-2 px-1 pb-3 {{ $tab === 'mail' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ __('dashboard.tab_mail') ?? __('Mail / SMTP') }}
            </button>
        </nav>
    </div>

    {{-- Tab: Theme --}}
    <div id="tab-theme" class="tab-panel {{ $tab !== 'theme' ? 'hidden' : '' }}">
        <form method="post" action="{{ route('dashboard.settings.update.theme') }}" class="max-w-3xl">
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Theme customization') }}</h2>
                <p class="mb-5 text-sm text-gray-500">{{ __('Customize colors and font used across the public site.') }}</p>
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
                            <option value="1rem" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '1rem')">{{ __('Extra large') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Header style') }}</label>
                        <select name="theme_header_style" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="transparent" @selected(old('theme_header_style', $settings->theme_header_style ?? '') === 'transparent')>{{ __('Transparent (over hero)') }}</option>
                            <option value="white" @selected(old('theme_header_style', $settings->theme_header_style ?? '') === 'white')>{{ __('Solid white') }}</option>
                            <option value="dark" @selected(old('theme_header_style', $settings->theme_header_style ?? '') === 'dark')>{{ __('Solid dark') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Footer style') }}</label>
                        <select name="theme_footer_style" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="dark" @selected(old('theme_footer_style', $settings->theme_footer_style ?? '') === 'dark')>{{ __('Dark') }}</option>
                            <option value="light" @selected(old('theme_footer_style', $settings->theme_footer_style ?? '') === 'light')>{{ __('Light') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Button style') }}</label>
                        <select name="theme_button_style" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="rounded" @selected(old('theme_button_style', $settings->theme_button_style ?? '') === 'rounded')>{{ __('Rounded') }}</option>
                            <option value="square" @selected(old('theme_button_style', $settings->theme_button_style ?? '') === 'square')>{{ __('Square') }}</option>
                            <option value="pill" @selected(old('theme_button_style', $settings->theme_button_style ?? '') === 'pill')>{{ __('Pill') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Section spacing') }}</label>
                        <select name="theme_section_spacing" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="compact" @selected(old('theme_section_spacing', $settings->theme_section_spacing ?? '') === 'compact')>{{ __('Compact') }}</option>
                            <option value="default" @selected(old('theme_section_spacing', $settings->theme_section_spacing ?? '') === 'default')>{{ __('Default') }}</option>
                            <option value="spacious" @selected(old('theme_section_spacing', $settings->theme_section_spacing ?? '') === 'spacious')>{{ __('Spacious') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Theme style') }}</label>
                        <select name="theme_style" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="default" @selected(old('theme_style', $settings->theme_style ?? 'default') === 'default')>{{ __('Default') }}</option>
                            <option value="modern" @selected(old('theme_style', $settings->theme_style ?? 'default') === 'modern')>{{ __('Modern') }}</option>
                            <option value="classic" @selected(old('theme_style', $settings->theme_style ?? 'default') === 'classic')>{{ __('Classic') }}</option>
                            <option value="minimal" @selected(old('theme_style', $settings->theme_style ?? 'default') === 'minimal')>{{ __('Minimal') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Adjusts the overall look of the dashboard (radius, heading weight, card shadow).') }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Save settings') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Tab: Localization --}}
    <div id="tab-localization" class="tab-panel {{ $tab !== 'localization' ? 'hidden' : '' }}">
        <form method="post" action="{{ route('dashboard.settings.update.localization') }}" class="max-w-3xl">
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Localization') }}</h2>
                <p class="mb-5 text-sm text-gray-500">{{ __('Timezone, date format, time format, and default language.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Timezone') }}</label>
                        <select name="timezone" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="UTC" @selected(old('timezone', $settings->timezone) === 'UTC')>UTC</option>
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $settings->timezone) === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @php
                            $currentTz = $settings->timezone ?? 'UTC';
                            $tzNow = new \DateTime('now', new \DateTimeZone($currentTz));
                        @endphp
                        <p class="mt-1 text-xs text-gray-500">{{ __('Current time in this timezone') }}: <strong>{{ $tzNow->format('h:i:s A, d M Y') }}</strong></p>
                    </div>
                    <div>
                        <label for="default_locale" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Default site language') }}</label>
                        <select id="default_locale" name="default_locale"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="en" @selected(old('default_locale', $settings->default_locale) === 'en')>English</option>
                            <option value="bn" @selected(old('default_locale', $settings->default_locale) === 'bn')>বাংলা (Bengali)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('The language first-time visitors see.') }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date format') }}</label>
                        <input type="text" name="date_format" value="{{ old('date_format', $settings->date_format ?? 'Y-m-d') }}" placeholder="Y-m-d"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">{{ __('PHP date format string. Example: d/m/Y, F j, Y') }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Time format') }}</label>
                        <input type="text" name="time_format" value="{{ old('time_format', $settings->time_format ?? 'H:i') }}" placeholder="H:i"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">{{ __('PHP time format string. Example: h:i A, H:i:s') }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Save settings') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Tab: Payment --}}
    <div id="tab-payment" class="tab-panel {{ $tab !== 'payment' ? 'hidden' : '' }}">
        <form method="post" action="{{ route('dashboard.settings.update.payment') }}" class="max-w-3xl">
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Payment settings') }}</h2>
                <p class="mb-5 text-sm text-gray-500">{{ __('Configure payment gateways for online fee collection.') }}</p>

                {{-- bKash --}}
                <div class="mb-6 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">bKash</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.bkash_merchant_number') ?? 'bKash Merchant Number' }}</label>
                            <input type="text" name="bkash_merchant_number" value="{{ old('bkash_merchant_number', $settings->bkash_merchant_number) }}" placeholder="01XXXXXXXXX"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.bkash_api_key') ?? 'bKash API Key' }}</label>
                            <input type="text" name="bkash_api_key" value="{{ old('bkash_api_key', $settings->bkash_api_key) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.bkash_api_secret') ?? 'bKash API Secret' }}</label>
                            <input type="password" name="bkash_api_secret" value="{{ old('bkash_api_secret', $settings->bkash_api_secret) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.bkash_username') ?? 'bKash Username' }}</label>
                            <input type="text" name="bkash_username" value="{{ old('bkash_username', $settings->bkash_username) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.bkash_password') ?? 'bKash Password' }}</label>
                            <input type="password" name="bkash_password" value="{{ old('bkash_password', $settings->bkash_password) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.bkash_app_key') ?? 'bKash App Key' }}</label>
                            <input type="text" name="bkash_app_key" value="{{ old('bkash_app_key', $settings->bkash_app_key) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.bkash_app_secret') ?? 'bKash App Secret' }}</label>
                            <input type="password" name="bkash_app_secret" value="{{ old('bkash_app_secret', $settings->bkash_app_secret) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="bkash_sandbox" value="0">
                                <input type="checkbox" name="bkash_sandbox" value="1" @checked(old('bkash_sandbox', $settings->bkash_sandbox ?? true))
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>{{ __('dashboard.bkash_sandbox') ?? 'Sandbox Mode' }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Nagad --}}
                <div class="mb-6 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">Nagad</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.nagad_merchant_number') ?? 'Nagad Merchant Number' }}</label>
                            <input type="text" name="nagad_merchant_number" value="{{ old('nagad_merchant_number', $settings->nagad_merchant_number) }}" placeholder="01XXXXXXXXX"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- General Payment --}}
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __('General') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.currency') ?? 'Currency' }}</label>
                            <select name="currency" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="BDT" @selected(old('currency', $settings->currency ?? 'BDT') === 'BDT')>BDT (৳)</option>
                                <option value="USD" @selected(old('currency', $settings->currency ?? 'BDT') === 'USD')>USD ($)</option>
                                <option value="INR" @selected(old('currency', $settings->currency ?? 'BDT') === 'INR')>INR (₹)</option>
                                <option value="PKR" @selected(old('currency', $settings->currency ?? 'BDT') === 'PKR')>PKR (₨)</option>
                                <option value="EUR" @selected(old('currency', $settings->currency ?? 'BDT') === 'EUR')>EUR (€)</option>
                                <option value="GBP" @selected(old('currency', $settings->currency ?? 'BDT') === 'GBP')>GBP (£)</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.default_payment_method') ?? 'Default Payment Method' }}</label>
                            <select name="default_payment_method" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="bkash" @selected(old('default_payment_method', $settings->default_payment_method ?? 'bkash') === 'bkash')>bKash</option>
                                <option value="nagad" @selected(old('default_payment_method', $settings->default_payment_method ?? 'bkash') === 'nagad')>Nagad</option>
                                <option value="cash" @selected(old('default_payment_method', $settings->default_payment_method ?? 'bkash') === 'cash')>{{ __('Cash') }}</option>
                                <option value="bank" @selected(old('default_payment_method', $settings->default_payment_method ?? 'bkash') === 'bank')>{{ __('Bank') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Save settings') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Tab: Library --}}
    <div id="tab-library" class="tab-panel {{ $tab !== 'library' ? 'hidden' : '' }}">
        <form method="post" action="{{ route('dashboard.settings.update.library') }}" class="max-w-3xl">
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Library settings') }}</h2>
                <p class="mb-5 text-sm text-gray-500">{{ __('Configure library rules, late fees, and borrowing limits.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.late_fee_per_day') ?? 'Late Fee (per day)' }}</label>
                        <input type="number" step="0.01" min="0" name="late_fee_per_day" value="{{ old('late_fee_per_day', $librarySettings->late_fee_per_day ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.max_books_per_student') ?? 'Max Books per Student' }}</label>
                        <input type="number" min="1" name="max_books_per_student" value="{{ old('max_books_per_student', $librarySettings->max_books_per_student ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.max_books_per_teacher') ?? 'Max Books per Teacher' }}</label>
                        <input type="number" min="1" name="max_books_per_teacher" value="{{ old('max_books_per_teacher', $librarySettings->max_books_per_teacher ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('dashboard.issue_duration_days') ?? 'Issue Duration (days)' }}</label>
                        <input type="number" min="1" name="issue_duration_days" value="{{ old('issue_duration_days', $librarySettings->issue_duration_days ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Save settings') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Tab: Academic --}}
    <div id="tab-academic" class="tab-panel {{ $tab !== 'academic' ? 'hidden' : '' }}">
        <form method="post" action="{{ route('dashboard.settings.update.general') }}" class="max-w-3xl">
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Academic settings') }}</h2>
                <p class="mb-5 text-sm text-gray-500">{{ __('School identity, academic year, and general display options.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Established year') }}</label>
                        <input type="number" name="established_year" value="{{ old('established_year', $settings->established_year ?? '') }}" min="1900" max="{{ date('Y') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('School motto') }}</label>
                        <input type="text" name="tagline" value="{{ old('tagline', $settings->tagline ?? '') }}" placeholder="{{ __('Excellence in Education') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Website URL') }}</label>
                        <input type="url" name="website_url" value="{{ old('website_url', $settings->website_url ?? '') }}" placeholder="https://yourschool.edu"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Academic start month') }}</label>
                        <select name="academic_start_month" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                                <option value="{{ $i + 1 }}" @selected(old('academic_start_month', $settings->academic_start_month ?? 1) == ($i + 1))>{{ __($m) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Footer description') }}</label>
                        <textarea name="footer_description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('footer_description', $settings->footer_description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Save settings') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Tab: SMS --}}
    <div id="tab-sms" class="tab-panel {{ $tab !== 'sms' ? 'hidden' : '' }}">
        <form method="post" action="{{ route('dashboard.settings.update.general') }}" class="max-w-3xl">
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('SMS settings') }}</h2>
                <p class="mb-5 text-sm text-gray-500">{{ __('Configure SMS gateway and notification preferences.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('SMS sender ID') }}</label>
                        <input type="text" name="sms_sender_id" value="{{ old('sms_sender_id', $settings->sms_sender_id ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Twilio SID') }}</label>
                        <input type="text" name="twilio_sid" value="{{ old('twilio_sid', $settings->twilio_sid ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Twilio Auth Token') }}</label>
                        <input type="password" name="twilio_auth_token" value="{{ old('twilio_auth_token', $settings->twilio_auth_token ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Twilio From Number') }}</label>
                        <input type="text" name="twilio_from_number" value="{{ old('twilio_from_number', $settings->twilio_from_number ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Absence SMS template') }}</label>
                        <textarea name="absence_sms_template" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('absence_sms_template', $settings->absence_sms_template ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Available placeholders: :student_name, :date, :class') }}</p>
                    </div>
                    <div>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="send_absence_sms" value="0">
                            <input type="checkbox" name="send_absence_sms" value="1" @checked(old('send_absence_sms', $settings->send_absence_sms ?? false))
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            {{ __('Send absence SMS to guardians') }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Save settings') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Tab: Mail / SMTP --}}
    <div id="tab-mail" class="tab-panel {{ $tab !== 'mail' ? 'hidden' : '' }}">
        <div class="max-w-3xl">
            <form method="post" action="{{ route('dashboard.settings.update.mail') }}">
                @csrf
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Mail / SMTP settings') }}</h2>
                    <p class="mb-5 text-sm text-gray-500">{{ __('Configure an SMTP server so the application can send transactional emails.') }}</p>

                    <div class="mb-6 flex items-center gap-3">
                        <input type="hidden" name="mail_enabled" value="0">
                        <input type="checkbox" name="mail_enabled" value="1" id="mail_enabled" @checked(old('mail_enabled', $settings->mail_enabled ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="mail_enabled" class="text-sm font-medium text-gray-700">{{ __('Enable SMTP sending') }}</label>
                        <p class="text-xs text-gray-500">{{ __('When disabled, mail falls back to the log driver.') }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Provider preset') }}</label>
                        <select id="mail-preset" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">{{ __('Custom / Generic SMTP') }}</option>
                            @foreach($mailPresets as $code => $preset)
                                <option value="{{ $code }}" data-host="{{ $preset['host'] }}" data-port="{{ $preset['port'] }}" data-encryption="{{ $preset['encryption'] }}" @selected($settings->mail_host === $preset['host'])>{{ ucfirst($code) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Selecting a preset fills in the server defaults below.') }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Driver') }}</label>
                            <select name="mail_driver" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="smtp" @selected(old('mail_driver', $settings->mail_driver ?? 'smtp') === 'smtp')>SMTP</option>
                                <option value="log" @selected(old('mail_driver', $settings->mail_driver ?? 'smtp') === 'log')>{{ __('Log (testing)') }}</option>
                                <option value="mailgun" @selected(old('mail_driver', $settings->mail_driver ?? 'smtp') === 'mailgun')>Mailgun</option>
                                <option value="ses" @selected(old('mail_driver', $settings->mail_driver ?? 'smtp') === 'ses')>Amazon SES</option>
                                <option value="postmark" @selected(old('mail_driver', $settings->mail_driver ?? 'smtp') === 'postmark')>Postmark</option>
                                <option value="resend" @selected(old('mail_driver', $settings->mail_driver ?? 'smtp') === 'resend')>Resend</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">{{ __('For SMTP-based providers keep this on SMTP.') }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Host') }}</label>
                            <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $settings->mail_host ?? '') }}" placeholder="smtp.example.com"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Port') }}</label>
                            <input type="text" name="mail_port" id="mail_port" value="{{ old('mail_port', $settings->mail_port ?? '') }}" placeholder="587"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Encryption') }}</label>
                            <select name="mail_encryption" id="mail_encryption" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">None</option>
                                <option value="tls" @selected(old('mail_encryption', $settings->mail_encryption ?? '') === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('mail_encryption', $settings->mail_encryption ?? '') === 'ssl')>SSL</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Username') }}</label>
                            <input type="text" name="mail_username" value="{{ old('mail_username', $settings->mail_username ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                            <input type="password" name="mail_password" value="{{ old('mail_password', $settings->mail_password ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('From address') }}</label>
                            <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings->mail_from_address ?? '') }}" placeholder="noreply@example.com"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('From name') }}</label>
                            <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings->mail_from_name ?? '') }}" placeholder="{{ config('app.name') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Test recipient') }}</label>
                            <input type="email" name="mail_test_recipient" value="{{ old('mail_test_recipient', $settings->mail_test_recipient ?? '') }}" placeholder="admin@example.com"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-500">{{ __('Optional. Saved for the one-click test button.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ __('Save settings') }}
                    </button>
                </div>
            </form>

            <form method="post" action="{{ route('dashboard.settings.test.mail') }}" class="mt-6">
                @csrf
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Send test email') }}</h3>
                    <p class="mb-4 text-sm text-gray-500">{{ __('Save your settings first, then send a test email to verify the connection.') }}</p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Recipient') }}</label>
                            <input type="email" name="to" value="{{ old('to', $settings->mail_test_recipient ?? '') }}" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                            {{ __('Send test email') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tab switching
            var tabs = document.querySelectorAll('.tab-link');
            var panels = document.querySelectorAll('.tab-panel');
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var target = this.dataset.tab;
                    // Update URL hash without reload
                    var url = new URL(window.location);
                    url.searchParams.set('tab', target);
                    window.history.replaceState({}, '', url);
                    // Update tab styles
                    tabs.forEach(function(t) {
                        t.classList.remove('border-indigo-600', 'text-indigo-600');
                        t.classList.add('border-transparent', 'text-gray-500');
                    });
                    this.classList.remove('border-transparent', 'text-gray-500');
                    this.classList.add('border-indigo-600', 'text-indigo-600');
                    // Show/hide panels
                    panels.forEach(function(p) {
                        p.classList.add('hidden');
                    });
                    var panel = document.getElementById('tab-' + target);
                    if (panel) panel.classList.remove('hidden');
                });
            });

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

            // Mail provider preset autofill
            var presetSelect = document.getElementById('mail-preset');
            var hostInput = document.getElementById('mail_host');
            var portInput = document.getElementById('mail_port');
            var encryptionSelect = document.getElementById('mail_encryption');
            if (presetSelect && hostInput && portInput && encryptionSelect) {
                presetSelect.addEventListener('change', function() {
                    var option = this.selectedOptions[0];
                    if (!option.value) return;
                    hostInput.value = option.dataset.host;
                    portInput.value = option.dataset.port;
                    encryptionSelect.value = option.dataset.encryption || '';
                });
            }
        });
    </script>
@endsection
