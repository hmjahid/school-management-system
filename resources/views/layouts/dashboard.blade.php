<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard') . ' — ' . config('app.name', 'SchoolEase'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-50: oklch(0.97 0.02 250);
            --brand-100: oklch(0.93 0.04 250);
            --brand-500: oklch(0.55 0.18 250);
            --brand-600: oklch(0.48 0.18 250);
            --brand-700: oklch(0.42 0.16 250);
            --accent-500: oklch(0.68 0.18 55);
            --accent-600: oklch(0.62 0.18 55);
        }
    </style>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @endif
    @stack('head')
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-900 dark:text-slate-100">
    <div id="loading-bar" class="fixed left-0 top-0 z-[200] h-1 bg-brand-600 transition-all duration-300 ease-out" style="width:0; opacity:0;"></div>

    <div class="admin-shell flex h-screen overflow-hidden">
        <aside class="no-print flex w-64 flex-shrink-0 flex-col border-r border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800" id="sidebar">
            @include('partials.dashboard.sidebar')
        </aside>
        <div class="no-print fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden" id="sidebar-overlay"></div>

        <div class="flex flex-1 flex-col overflow-hidden">
            <header class="no-print flex h-16 flex-shrink-0 items-center gap-4 border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-800/95">
                @include('partials.dashboard.topbar')
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                @if (isset($breadcrumbs) && count($breadcrumbs))
                    <nav class="mb-4 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
                        @foreach ($breadcrumbs as $crumb)
                            @if ($crumb['url'])
                                <a href="{{ $crumb['url'] }}" class="transition-colors hover:text-slate-700 dark:hover:text-slate-200">{{ $crumb['label'] }}</a>
                                <svg class="h-4 w-4 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @else
                                <span class="font-medium text-slate-900 dark:text-slate-100">{{ $crumb['label'] }}</span>
                            @endif
                        @endforeach
                    </nav>
                @endif
                @if (session('status'))<div data-flash-toast data-type="success" data-message="{{ session('status') }}"></div>@endif
                @if (session('error'))<div data-flash-toast data-type="error" data-message="{{ session('error') }}"></div>@endif
                @if (session('info'))<div data-flash-toast data-type="info" data-message="{{ session('info') }}"></div>@endif
                @yield('content')
            </main>
        </div>
    </div>

    <div id="toast-root" class="toast-container" aria-live="polite" aria-atomic="true"></div>
    <div id="confirm-modal-root" class="fixed inset-0 z-[90] hidden items-center justify-center" aria-hidden="true">
        <div data-confirm-backdrop class="modal-backdrop"></div>
        <div class="modal-panel mx-4" role="alertdialog" aria-labelledby="confirm-title" aria-describedby="confirm-message">
            <h3 id="confirm-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100" data-confirm-title>Are you sure?</h3>
            <p id="confirm-message" class="mt-2 text-sm text-slate-600 dark:text-slate-400" data-confirm-message></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-confirm-cancel class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">Cancel</button>
                <button type="button" data-confirm-ok class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Confirm</button>
            </div>
        </div>
    </div>

    @stack('scripts')
    <script>
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"])');
            if (link && link.href && link.href.startsWith(window.location.origin)) {
                const bar = document.getElementById('loading-bar');
                if (bar) { bar.style.width = '30%'; bar.style.opacity = '1'; }
            }
        });
        window.addEventListener('load', () => {
            const bar = document.getElementById('loading-bar');
            if (bar) { bar.style.width = '100%'; setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0'; }, 400); }
        });
    </script>
</body>
</html>
