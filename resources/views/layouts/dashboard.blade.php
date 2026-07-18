<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if($siteSettings?->favicon_url)
        <link rel="icon" href="{{ $siteSettings->favicon_url }}">
    @endif
    <title>@yield('title', config('app.name', 'SchoolEase'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
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
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                        colors: {
                            brand: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' },
                            accent: { 500: '#f97316', 600: '#ea580c' },
                        },
                    },
                },
            };
        </script>
    @endif
</head>
<body class="admin-shell min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[200] focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-white">
        {{ __('Skip to content') }}
    </a>

    <input type="checkbox" id="dashboard-drawer" class="peer hidden" />

    <label for="dashboard-drawer" class="pointer-events-none fixed inset-0 z-30 bg-slate-900/40 opacity-0 backdrop-blur-[1px] transition-opacity peer-checked:pointer-events-auto peer-checked:opacity-100 md:hidden" aria-hidden="true"></label>

    <div class="flex min-h-screen w-full flex-col md:h-screen md:flex-row md:overflow-hidden">
        <aside class="fixed inset-y-0 left-0 z-40 flex w-[var(--sidebar-width)] -translate-x-full flex-col border-r border-slate-200/80 bg-white shadow-xl transition-transform duration-200 ease-out peer-checked:translate-x-0 md:static md:z-0 md:translate-x-0 md:shadow-none">
            @include('partials.dashboard.sidebar')
        </aside>

        <div class="relative z-0 flex min-h-0 flex-1 flex-col">
            @include('partials.dashboard.topbar')

            <main id="main-content" class="relative z-0 min-h-0 flex-1 overflow-y-auto bg-slate-50/80 p-4 md:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="toast-root" class="toast-container" aria-live="polite" aria-atomic="true"></div>

    @if (session('status'))
        <div data-flash-toast data-type="success" data-message="{{ session('status') }}" hidden></div>
    @endif
    @if ($errors->any())
        <div data-flash-toast data-type="error" data-message="{{ $errors->first() }}" hidden></div>
    @endif

    <x-confirm-modal />
</body>
</html>
