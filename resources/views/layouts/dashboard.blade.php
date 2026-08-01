<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $dashSettings = \App\Models\WebsiteSetting::getSettings(); @endphp
    <title>@yield('title', __('Dashboard') . ' — ' . ($dashSettings->school_name ?? config('app.name', 'SchoolEase')))</title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- PWA --}}
    <link rel="manifest" href="{{ route('site.manifest') }}">
    <meta name="theme-color" content="{{ $dashSettings->theme_primary_color ?? '#2563eb' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $dashSettings->school_name ?? config('app.name', 'SchoolEase') }}">
    <link rel="apple-touch-icon" href="{{ $dashSettings->logo_url ?: asset('favicon.ico') }}">
    <style>
        :root {
            --brand-50: color-mix(in srgb, {{ $dashSettings->theme_primary_color ?? '#2563eb' }} 10%, white);
            --brand-100: color-mix(in srgb, {{ $dashSettings->theme_primary_color ?? '#2563eb' }} 20%, white);
            --brand-500: {{ $dashSettings->theme_primary_color ?? '#2563eb' }};
            --brand-600: color-mix(in srgb, {{ $dashSettings->theme_primary_color ?? '#2563eb' }} 80%, black);
            --brand-700: color-mix(in srgb, {{ $dashSettings->theme_primary_color ?? '#2563eb' }} 65%, black);
            --accent-500: {{ $dashSettings->theme_secondary_color ?? '#f97316' }};
            --accent-600: color-mix(in srgb, {{ $dashSettings->theme_secondary_color ?? '#f97316' }} 80%, black);
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
        (function(){
            var bar = document.getElementById('loading-bar');
            if (!bar) return;
            var progress = 0;
            var timer = null;

            document.addEventListener('click', function(e) {
                var link = e.target.closest('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not([data-no-loading])');
                if (link && !link.closest('[data-no-loading]') && link.href && link.href.startsWith(window.location.origin) && link.href !== window.location.href) {
                    bar.style.opacity = '1';
                    progress = 0;
                    bar.style.width = '10%';
                    timer = setInterval(function() {
                        progress += 5;
                        if (progress > 90) { clearInterval(timer); return; }
                        bar.style.width = progress + '%';
                    }, 100);
                }
            });

            window.addEventListener('load', function() {
                if (timer) clearInterval(timer);
                if (bar) {
                    bar.style.width = '100%';
                    setTimeout(function() { bar.style.opacity = '0'; bar.style.width = '0'; }, 300);
                }
            });
        })();
    </script>

    {{-- Command Palette Search --}}
    <div id="dashboard-search-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-search-backdrop></div>
        <div class="relative mx-auto mt-[10vh] w-full max-w-xl px-4">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-3 dark:border-slate-700">
                    <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input id="dashboard-search-input" type="text" placeholder="{{ __('Search students, teachers, notices, classes...') }}" autocomplete="off" class="flex-1 bg-transparent text-sm text-slate-900 placeholder-slate-400 focus:outline-none dark:text-slate-100 dark:placeholder-slate-500">
                    <kbd class="hidden rounded border border-slate-300 bg-slate-100 px-1.5 py-0.5 text-[0.65rem] font-medium text-slate-500 sm:inline dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400">ESC</kbd>
                </div>
                <div id="dashboard-search-results" class="max-h-[50vh] overflow-y-auto p-2">
                    <p class="px-3 py-6 text-center text-sm text-slate-400">{{ __('Start typing to search...') }}</p>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var modal = document.getElementById('dashboard-search-modal');
        var input = document.getElementById('dashboard-search-input');
        var results = document.getElementById('dashboard-search-results');
        var backdrop = modal?.querySelector('[data-search-backdrop]');
        var debounceTimer = null;

        function openSearch() {
            if (!modal) return;
            modal.classList.remove('hidden');
            input?.focus();
            input.value = '';
            results.innerHTML = '<p class="px-3 py-6 text-center text-sm text-slate-400">{{ __("Start typing to search...") }}</p>';
        }
        function closeSearch() {
            if (!modal) return;
            modal.classList.add('hidden');
        }

        window.openDashboardSearch = openSearch;

        document.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                openSearch();
            }
            if (e.key === 'Escape') closeSearch();
        });

        backdrop?.addEventListener('click', closeSearch);

        input?.addEventListener('input', function() {
            var q = input.value.trim();
            clearTimeout(debounceTimer);
            if (q.length < 2) {
                results.innerHTML = '<p class="px-3 py-6 text-center text-sm text-slate-400">{{ __("Start typing to search...") }}</p>';
                return;
            }
            debounceTimer = setTimeout(function() {
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                fetch('{{ route("dashboard.search") }}?q=' + encodeURIComponent(q), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var items = data.data || [];
                    if (!items.length) {
                        results.innerHTML = '<p class="px-3 py-6 text-center text-sm text-slate-400">{{ __("No results found.") }}</p>';
                        return;
                    }
                    var typeIcons = {
                        student: '<svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>',
                        teacher: '<svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>',
                        class: '<svg class="h-5 w-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>',
                        notice: '<svg class="h-5 w-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/></svg>'
                    };
                    var typeLabels = { student: '{{ __("Student") }}', teacher: '{{ __("Teacher") }}', class: '{{ __("Class") }}', notice: '{{ __("Notice") }}' };
                    var html = '';
                    items.forEach(function(item) {
                        html += '<a href="' + item.url + '" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700">';
                        html += '<span class="shrink-0">' + (typeIcons[item.type] || '') + '</span>';
                        html += '<div class="min-w-0 flex-1">';
                        html += '<div class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">' + item.name + '</div>';
                        if (item.subtitle) html += '<div class="text-xs text-slate-500 dark:text-slate-400 truncate">' + item.subtitle + '</div>';
                        html += '</div>';
                        html += '<span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[0.6rem] font-semibold uppercase text-slate-500 dark:bg-slate-700 dark:text-slate-400">' + (typeLabels[item.type] || '') + '</span>';
                        html += '</a>';
                    });
                    results.innerHTML = html;
                })
                .catch(function() {
                    results.innerHTML = '<p class="px-3 py-6 text-center text-sm text-red-400">{{ __("Search failed. Please try again.") }}</p>';
                });
            }, 300);
        });
    })();
    </script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
