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
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } } };
        </script>
    @endif
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <input type="checkbox" id="dashboard-drawer" class="peer hidden" />

    {{-- Click-away overlay (mobile) --}}
    <label for="dashboard-drawer" class="pointer-events-none fixed inset-0 z-30 bg-black/40 opacity-0 transition-opacity peer-checked:pointer-events-auto peer-checked:opacity-100 md:hidden"></label>

    <div class="flex min-h-screen w-full flex-col md:h-screen md:flex-row md:overflow-hidden">
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white text-gray-900 shadow-xl transition-transform duration-200 ease-out peer-checked:translate-x-0 md:static md:z-0 md:translate-x-0 md:shadow-none">
            @include('partials.dashboard.sidebar')
        </aside>

        <div class="flex min-h-screen flex-1 flex-col overflow-hidden md:min-h-0">
            @include('partials.dashboard.topbar')

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
