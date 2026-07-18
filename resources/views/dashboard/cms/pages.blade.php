@extends('layouts.dashboard')

@section('title', __('Website pages') . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Website pages') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                {{ __('Pick a page to edit its text and images. Every page has a fixed structure, so you only ever see fields that matter for that page.') }}
            </p>
        </div>
        <a href="{{ route('dashboard.cms.edit', ['page' => 'site-ui']) }}" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700">
            {{ __('Global labels (nav, footer, home)') }}
        </a>
    </div>

    @php
        $defaultLocale = $siteSettings?->default_locale ?? config('app.locale', 'en');
        $localeLabel = $defaultLocale === 'bn' ? 'বাংলা (Bengali)' : 'English';
    @endphp
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm">
        <div class="flex items-center gap-2 text-indigo-900">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M7.75 2.5a.75.75 0 0 1 .75.75V4h3V3.25a.75.75 0 0 1 1.5 0V4h.5A2.75 2.75 0 0 1 16.25 6.75v8.5A2.75 2.75 0 0 1 13.5 18h-7A2.75 2.75 0 0 1 3.75 15.25v-8.5A2.75 2.75 0 0 1 6.5 4h.5V3.25a.75.75 0 0 1 .75-.75Z" />
            </svg>
            <span><strong>{{ __('Default site language:') }}</strong> {{ $localeLabel }}</span>
        </div>
        <a href="{{ route('dashboard.settings') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">
            {{ __('Change in school settings') }} →
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($pages as $p)
            @php
                $def = $registry[$p->page] ?? null;
                $label = $def['label'] ?? ucfirst(str_replace('-', ' ', $p->page));
                $description = $def['description'] ?? null;
            @endphp
            <a href="{{ route('dashboard.cms.edit', ['page' => $p->page]) }}"
                class="group flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <h3 class="text-base font-semibold text-gray-900 group-hover:text-indigo-700">{{ $label }}</h3>
                    @if ($p->is_active)
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">{{ __('Active') }}</span>
                    @else
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ __('Inactive') }}</span>
                    @endif
                </div>
                @if ($description)
                    <p class="text-sm text-gray-500">{{ $description }}</p>
                @endif
                <div class="mt-3 flex items-center justify-between text-xs">
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-600">{{ $p->page }}</code>
                    <span class="font-medium text-indigo-600 group-hover:text-indigo-800">{{ __('Edit') }} →</span>
                </div>
            </a>
        @empty
            <p class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                {{ __('No pages yet.') }}
            </p>
        @endforelse
    </div>
@endsection
