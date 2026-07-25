@php
    $settings = $siteSettings ?? \App\Models\WebsiteSetting::getSettings();
@endphp
@if($settings && ($settings->school_name || $settings->logo_url))
    <div class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm">
        @if($settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="{{ $settings->school_name }}" class="h-10 w-auto max-w-[140px] object-contain">
        @endif
        <div class="flex flex-col">
            <span class="font-semibold text-indigo-900">{{ $settings->school_name ?? config('app.name') }}</span>
            @if($settings->full_address)
                <span class="text-xs text-indigo-700">{{ $settings->full_address }}</span>
            @endif
        </div>
        @if($settings->established_year)
            <span class="ml-auto hidden rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800 sm:inline-block">
                {{ __('Est.') }} {{ $settings->established_year }}
            </span>
        @endif
    </div>
@endif
