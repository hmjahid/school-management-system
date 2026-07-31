<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Restore dark mode immediately to prevent flash on page load / language switch --}}
    <script>
    (function(){var k='school-dark-mode',v=localStorage.getItem(k);if(v==='1'||(v===null&&matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.classList.add('dark')})();
    </script>
    {{-- Inline dark toggle handler — works even if app.js hasn't loaded yet --}}
    <script>
    (function(){
        var KEY='school-dark-mode';
        function apply(on){document.documentElement.classList.toggle('dark',on);localStorage.setItem(KEY,on?'1':'0');}
        document.addEventListener('click',function(e){var t=e.target.closest('[data-dark-toggle]');if(!t)return;e.preventDefault();e.stopPropagation();apply(!document.documentElement.classList.contains('dark'));},true);
    })();
    </script>

    <title>@yield('title', $siteSettings->site_name ?? config('app.name', 'SchoolEase'))</title>

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', $siteSettings->meta_description ?? '')">
    <meta name="keywords" content="@yield('meta_keywords', $siteSettings->meta_keywords ?? '')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow">

    {{-- PWA --}}
    <link rel="manifest" href="{{ route('site.manifest') }}">
    <meta name="theme-color" content="{{ $siteSettings->theme_primary_color ?? '#2563eb' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $siteSettings->school_name ?? config('app.name', 'SchoolEase') }}">
    <link rel="apple-touch-icon" href="{{ $siteSettings->logo_url ?: asset('favicon.ico') }}">

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

    <style>
        :root {
            --theme-primary: {{ $siteSettings->theme_primary_color ?? '#2563eb' }};
            --theme-secondary: {{ $siteSettings->theme_secondary_color ?? '#f97316' }};
            --theme-font: {{ $siteSettings->theme_font_family ?: "'Inter', sans-serif" }};
            --theme-radius: {{ $siteSettings->theme_border_radius ?: '0.75rem' }};
            --theme-header-style: {{ $siteSettings->theme_header_style ?? 'transparent' }};
            --theme-footer-style: {{ $siteSettings->theme_footer_style ?? 'dark' }};
            --theme-button-style: {{ $siteSettings->theme_button_style ?? 'rounded' }};
            --theme-section-spacing: {{ $siteSettings->theme_section_spacing ?? 'default' }};

            --brand-50: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 10%, white);
            --brand-100: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 20%, white);
            --brand-400: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 70%, white);
            --brand-500: {{ $siteSettings->theme_primary_color ?? '#2563eb' }};
            --brand-600: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 80%, black);
            --brand-700: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 65%, black);
            --brand-800: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 50%, black);
            --brand-900: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 35%, black);
            --accent-500: {{ $siteSettings->theme_secondary_color ?? '#f97316' }};
            --accent-600: color-mix(in srgb, {{ $siteSettings->theme_secondary_color ?? '#f97316' }} 80%, black);
        }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        @keyframes noticeScrollUp { 0% { transform: translateY(0); } 100% { transform: translateY(-50%); } }
        .animate-marquee { animation: marquee 12s linear infinite; }
        .animate-marquee:hover { animation-play-state: paused; }
        .notice-scroll-content { display: flex; flex-direction: column; gap: 10px; animation: noticeScrollUp var(--scroll-duration, 15s) linear infinite; }
        .notice-scroll-container:hover .notice-scroll-content { animation-play-state: paused; }

        .theme-bg-primary { background-color: var(--theme-primary); }
        .theme-bg-secondary { background-color: var(--theme-secondary); }
        .theme-text-primary { color: var(--theme-primary); }
        .theme-text-secondary { color: var(--theme-secondary); }
        .theme-border-primary { border-color: var(--theme-primary); }
        .theme-from-primary { --tw-gradient-from: var(--theme-primary); }
        .theme-to-secondary { --tw-gradient-to: var(--theme-secondary); }
        .theme-ring-primary { --tw-ring-color: var(--theme-primary); }
        .theme-btn-primary { background-color: var(--theme-primary); color: #fff; border-radius: var(--theme-radius); }
        .theme-btn-primary:hover { opacity: 0.9; }
        .theme-btn-secondary { background-color: var(--theme-secondary); color: #fff; border-radius: var(--theme-radius); }
        .theme-btn-secondary:hover { opacity: 0.9; }
        .theme-card { border-radius: var(--theme-radius); }

        /* Theme overrides — apply primary/secondary to common Tailwind utilities */
        .bg-blue-600 { background-color: var(--theme-primary) !important; }
        .bg-blue-700 { background-color: var(--theme-primary) !important; filter: brightness(0.85); }
        .bg-blue-50 { background-color: color-mix(in srgb, var(--theme-primary) 10%, white) !important; }
        .text-blue-600 { color: var(--theme-primary) !important; }
        .text-blue-700 { color: var(--theme-primary) !important; }
        .border-blue-200 { border-color: color-mix(in srgb, var(--theme-primary) 30%, white) !important; }
        .border-blue-500 { border-color: var(--theme-primary) !important; }
        .ring-blue-500 { --tw-ring-color: var(--theme-primary) !important; }
        .focus\:border-blue-500:focus { border-color: var(--theme-primary) !important; }
        .focus\:ring-blue-500:focus { --tw-ring-color: var(--theme-primary) !important; }
        .bg-orange-500 { background-color: var(--theme-secondary) !important; }
        .bg-orange-600 { background-color: var(--theme-secondary) !important; filter: brightness(0.85); }
        .text-orange-600 { color: var(--theme-secondary) !important; }
        .text-orange-700 { color: var(--theme-secondary) !important; }
        .from-orange-400 { --tw-gradient-from: var(--theme-secondary) !important; }
        .to-orange-600 { --tw-gradient-to: var(--theme-secondary) !important; }
        .hover\:bg-blue-700:hover { background-color: color-mix(in srgb, var(--theme-primary) 80%, black) !important; }
        .hover\:bg-orange-600:hover { background-color: color-mix(in srgb, var(--theme-secondary) 80%, black) !important; }

        /* Header style */
        @if(($siteSettings->theme_header_style ?? 'transparent') === 'white')
            .site-header { background: #fff !important; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        @elseif(($siteSettings->theme_header_style ?? 'transparent') === 'dark')
            .site-header { background: #1e293b !important; }
        @endif

        /* Button style overrides */
        @php $btnStyle = $siteSettings->theme_button_style ?? 'rounded'; @endphp
        @if($btnStyle === 'pill')
            .theme-btn-primary, .theme-btn-secondary, a.inline-flex, button.rounded-lg { border-radius: 9999px !important; }
        @elseif($btnStyle === 'square')
            .theme-btn-primary, .theme-btn-secondary { border-radius: 0 !important; }
        @endif

        /* Section spacing */
        @php $spacing = $siteSettings->theme_section_spacing ?? 'default'; @endphp
        @if($spacing === 'compact')
            section.py-20, section[class*="py-20"] { padding-top: 3rem !important; padding-bottom: 3rem !important; }
        @elseif($spacing === 'spacious')
            section.py-20, section[class*="py-20"] { padding-top: 6rem !important; padding-bottom: 6rem !important; }
        @endif
    </style>
</head>
<body class="flex min-h-screen flex-col bg-surface font-sans text-on-surface antialiased" style="font-family: var(--theme-font);">
    {{-- Loading bar --}}
    <div id="loading-bar" class="fixed left-0 top-0 z-[200] h-1 bg-brand-600 transition-all duration-300 ease-out" style="width:0; opacity:0;"></div>

    {{-- Announcement ticker bar --}}
    @php
        $tickerAnnouncements = \Illuminate\Support\Facades\Schema::hasTable('announcements')
            ? \App\Models\Announcement::query()->published()->active()->forHeader()->orderByDesc('id')->limit(5)->get()
            : collect();
    @endphp
    @if($tickerAnnouncements->isNotEmpty())
        <div class="no-print text-xs overflow-hidden bg-blue-600 text-white py-1.5">
            <div class="marquee-track flex w-max items-center gap-6 whitespace-nowrap animate-marquee">
                @for($i = 0; $i < 6; $i++)
                    @foreach($tickerAnnouncements as $ann)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 font-semibold bg-white/20 px-2 py-0.5 rounded text-[0.65rem] uppercase tracking-wider">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/></svg>
                                {{ site_ui('nav.announcements') }}
                            </span>
                            <span>{{ $ann->localizedTitle() }}</span>
                        </span>
                    @endforeach
                    <span class="mx-2 text-white/30">|</span>
                @endfor
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
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
