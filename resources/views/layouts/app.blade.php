<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $siteSettings->site_name ?? config('app.name', 'SchoolEase'))</title>

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', $siteSettings->meta_description ?? '')">
    <meta name="keywords" content="@yield('meta_keywords', $siteSettings->meta_keywords ?? '')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', $siteSettings->site_name ?? config('app.name'))">
    <meta property="og:description" content="@yield('og_description', $siteSettings->meta_description ?? '')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:site_name" content="{{ $siteSettings->site_name ?? config('app.name') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $siteSettings->site_name ?? config('app.name'))">
    <meta name="twitter:description" content="@yield('og_description', $siteSettings->meta_description ?? '')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    {{-- Schema.org Organization --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "EducationalOrganization",
        "name": "{{ $siteSettings->site_name ?? config('app.name') }}",
        "url": "{{ config('app.url') }}",
        "logo": "{{ $siteSettings->logo_url ?? '' }}",
        "address": {
            "@@type": "PostalAddress",
            "addressCountry": "BD"
        }
    }
    </script>

    {{-- Vite or CDN fallback --}}
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @endif

    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-surface font-sans text-on-surface antialiased">
    {{-- Loading bar --}}
    <div id="loading-bar" class="fixed left-0 top-0 z-[200] h-1 bg-brand-600 transition-all duration-300 ease-out" style="width:0; opacity:0;"></div>

    {{-- Announcement ticker bar --}}
    @if(isset($announcements) && $announcements->isNotEmpty())
        <div class="no-print bg-brand-600 text-white text-xs py-1.5 overflow-hidden">
            <div class="flex items-center gap-2 animate-marquee whitespace-nowrap">
                <span class="inline-flex items-center gap-1 font-semibold bg-white/20 px-2 py-0.5 rounded">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/></svg>
                    Notice
                </span>
                @foreach($announcements as $ann)
                    <span>{{ $ann->title }}</span>
                    @if(!$loop->last)<span class="opacity-40">•</span>@endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Navigation --}}
    @include('partials.site.nav')

    {{-- Main content --}}
    <main class="flex-1">
        {{-- Flash messages --}}
        @if (session('status'))
            <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8" data-flash-toast data-type="success" data-message="{{ session('status') }}"></div>
        @endif
        @if (session('error'))
            <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8" data-flash-toast data-type="error" data-message="{{ session('error') }}"></div>
        @endif

        @yield('content')
    </main>

    {{-- Footer --}}
    @include('partials.site.footer')

    {{-- Toast container --}}
    <div id="toast-root" class="toast-container" aria-live="polite" aria-atomic="true"></div>

    {{-- Confirm modal --}}
    <div id="confirm-modal-root" class="fixed inset-0 z-[90] hidden items-center justify-center" aria-hidden="true">
        <div data-confirm-backdrop class="modal-backdrop"></div>
        <div class="modal-panel mx-4" role="alertdialog" aria-labelledby="confirm-title" aria-describedby="confirm-message">
            <h3 id="confirm-title" class="text-lg font-semibold text-slate-900" data-confirm-title>Are you sure?</h3>
            <p id="confirm-message" class="mt-2 text-sm text-slate-600" data-confirm-message></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-confirm-cancel class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" data-confirm-ok class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Confirm</button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
