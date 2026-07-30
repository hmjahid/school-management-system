@php
    $user = auth()->user();
@endphp

{{-- Mobile sidebar toggle --}}
<button type="button" id="sidebar-toggle" class="-ml-2 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="Toggle sidebar">
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

{{-- Live Timezone Clock --}}
@php
    $tz = $siteSettings?->timezone ?? 'UTC';
    $tzObj = new DateTimeZone($tz);
    $now = new DateTime('now', $tzObj);
@endphp
<div class="hidden items-center gap-2 text-xs text-slate-500 md:flex" id="live-clock-container" data-timezone="{{ $tz }}">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span id="live-clock">{{ $now->format('l, d M Y, h:i:s A') }} <span class="font-medium">{{ $now->format('T') }}</span></span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var clockEl = document.getElementById('live-clock');
    var container = document.getElementById('live-clock-container');
    if (!clockEl || !container) return;
    var timezone = container.dataset.timezone || 'UTC';
    function updateClock() {
        var now = new Date();
        var options = {
            timeZone: timezone,
            weekday: 'long',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        var formatter = new Intl.DateTimeFormat('en-US', options);
        var parts = formatter.formatToParts(now);
        var partsMap = {};
        parts.forEach(function(p) { partsMap[p.type] = p.value; });
        var dateStr = partsMap.weekday + ', ' + partsMap.day + ' ' + partsMap.month + ' ' + partsMap.year + ', ' + partsMap.hour + ':' + partsMap.minute + ':' + partsMap.second + ' ' + partsMap.dayPeriod;
        var tzName = timezone.split('/').pop().replace(/_/g, ' ');
        clockEl.textContent = dateStr + ' ' + tzName;
    }
    updateClock();
    setInterval(updateClock, 1000);
});
</script>

{{-- Spacer --}}
<div class="flex-1"></div>

{{-- Right actions --}}
<div class="flex items-center gap-2">
    {{-- Website link --}}
    <a href="/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" title="{{ __('Website') }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
        <span class="hidden md:inline">{{ __('Website') }}</span>
    </a>

    {{-- Search --}}
    <button type="button" onclick="typeof openDashboardSearch === 'function' && openDashboardSearch()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="{{ __('Search') }}" title="{{ __('Search') }}">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </button>

    {{-- Language switcher --}}
    <div class="relative" data-lang-menu-root>
        <button type="button" data-lang-menu-toggle class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="{{ __('Language') }}" title="{{ __('Language') }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
        </button>
        <div data-lang-menu-panel class="absolute right-0 top-full z-50 mt-2 hidden w-36 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-slate-700 dark:bg-slate-800">
            @foreach (config('school.supported_locales', ['en']) as $loc)
                @php $locLabel = ['en' => 'English', 'bn' => 'বাংলা'][$loc] ?? strtoupper($loc); @endphp
                <a href="{{ route('dashboard.locale.switch', ['locale' => $loc]) }}"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm {{ app()->getLocale() === $loc ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-900/20 dark:text-brand-400' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    @if(app()->getLocale() === $loc)
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4L8.5 12l6.8-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                    @endif
                    <span class="flex-1">{{ $locLabel }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Dark mode toggle --}}
    <button type="button" data-dark-toggle class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="Toggle dark mode">
        <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    </button>

    {{-- Notifications --}}
    <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="Notifications">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
    </a>

    {{-- User avatar dropdown --}}
    <div class="relative" data-user-menu-root>
        <button type="button" data-user-menu-toggle class="flex items-center gap-2 rounded-lg p-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700" aria-expanded="false">
            <img src="{{ $user->profile_photo_url }}" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-600">
            <span class="hidden md:inline">{{ $user->name }}</span>
            <svg class="hidden h-4 w-4 text-slate-400 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div data-user-menu-panel class="absolute right-0 top-full z-50 mt-2 hidden w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-slate-700 dark:bg-slate-800" role="menu">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700" role="menuitem">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                {{ __('dashboard.dashboard') }}
            </a>
            <a href="{{ route('dashboard.profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700" role="menuitem">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ __('dashboard.my_profile') }}
            </a>
            <a href="{{ route('dashboard.settings.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700" role="menuitem">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.066 2.573c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('dashboard.school_settings') }}
            </a>
            <hr class="my-1 border-slate-100 dark:border-slate-700">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20" role="menuitem">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    {{ __('dashboard.logout') }}
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        sidebar.classList.toggle('lg:translate-x-0');
        overlay.classList.toggle('hidden');
        document.body.classList.toggle('overflow-hidden');
    });
    document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('lg:translate-x-0');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });
    (function(){
        var langRoot = document.querySelector('[data-lang-menu-root]');
        if (langRoot) {
            var toggle = langRoot.querySelector('[data-lang-menu-toggle]');
            var panel = langRoot.querySelector('[data-lang-menu-panel]');
            toggle?.addEventListener('click', function(e) {
                e.stopPropagation();
                panel?.classList.toggle('hidden');
                var userRoot = document.querySelector('[data-user-menu-root]');
                var up = userRoot?.querySelector('[data-user-menu-panel]');
                if (up) up.classList.add('hidden');
                var ub = userRoot?.querySelector('[data-user-menu-toggle]');
                if (ub) ub.setAttribute('aria-expanded', 'false');
            });
            document.addEventListener('click', function(e) {
                if (!e.target.closest('[data-lang-menu-root]')) panel?.classList.add('hidden');
            });
        }
    })();
</script>
