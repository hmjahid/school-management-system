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
                            <option value="0.75rem" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '0.75rem')">{{ __('Large') }}</option>
                            <option value="1rem" @selected(old('theme_border_radius', $settings->theme_border_radius ?? '') === '1rem')">{{ __('Extra large') }}</option>
                        </select>
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
        });
    </script>
@endsection
