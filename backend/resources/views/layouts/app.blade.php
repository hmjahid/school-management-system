<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if($siteSettings?->favicon_url)
        <link rel="icon" href="{{ $siteSettings->favicon_url }}">
    @endif
    <title>@yield('title', ($siteSettings->school_name ?? config('app.name', 'School')))</title>
    <link rel="canonical" href="{{ url()->current() }}">
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @elseif($siteSettings?->meta_description ?? false)
        <meta name="description" content="{{ $siteSettings->meta_description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $siteSettings->school_name ?? config('app.name', 'School') }}">
    <meta property="og:title" content="@yield('title', ($siteSettings->school_name ?? config('app.name', 'School')))">
    @hasSection('meta_description')
        <meta property="og:description" content="@yield('meta_description')">
    @elseif($siteSettings?->meta_description ?? false)
        <meta property="og:description" content="{{ $siteSettings->meta_description }}">
    @endif
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteSettings->school_name ?? config('app.name', 'School'),
            'url' => url('/'),
            'email' => $siteSettings->email ?? null,
            'telephone' => $siteSettings->phone ?? null,
            'address' => ($siteSettings && ($siteSettings->full_address ?? $siteSettings->address)) ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $siteSettings->full_address ?? $siteSettings->address,
                'addressLocality' => $siteSettings->city ?? null,
                'addressCountry' => $siteSettings->country ?? null,
            ] : null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @stack('schema')
    @stack('head')
    @if(config('school.google_analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('school.google_analytics_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json(config('school.google_analytics_id')));
        </script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    },
                },
            };
        </script>
    @endif
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-gray-900 antialiased">
    @include('partials.site.nav')

    <main class="flex w-full flex-1 flex-col">
        @if (session('status'))
            <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="border-b border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <div class="mx-auto max-w-7xl">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.site.footer')
</body>
</html>
