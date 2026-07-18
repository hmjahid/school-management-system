@php
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
    $roleName = $user?->getRoleNames()->first() ?? __('User');
    $initials = collect(explode(' ', $user?->name ?? 'U'))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
@endphp

<header class="relative z-30 flex h-[4.25rem] flex-shrink-0 items-center justify-between gap-4 overflow-visible border-b border-slate-200/80 bg-white px-4 shadow-sm md:px-6">
    <div class="flex min-w-0 items-center gap-3">
        <label for="dashboard-drawer" class="inline-flex cursor-pointer items-center justify-center rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 md:hidden" aria-label="{{ __('Open menu') }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </label>
        <div class="hidden min-w-0 sm:block">
            <p class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Welcome back') }}</p>
            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
        </div>
    </div>

    <div class="flex items-center gap-1.5 sm:gap-2">
        <a href="{{ route('home') }}" target="_blank" rel="noopener"
            class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 sm:inline-flex">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            {{ __('Public site') }}
        </a>

        @auth
            <div class="relative" data-notifications-root>
                <button type="button" data-notifications-toggle aria-label="{{ __('Notifications') }}" aria-haspopup="true" aria-expanded="false"
                    class="relative inline-flex items-center justify-center rounded-lg p-2 text-slate-600 transition hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    @if ($unreadCount > 0)
                        <span data-notifications-badge class="absolute right-1 top-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[0.6rem] font-bold leading-none text-white">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    @endif
                </button>
                <div data-notifications-panel class="absolute right-0 z-[100] mt-2 hidden w-80 max-w-[90vw] origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" role="menu">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Notifications') }}</p>
                        @if ($unreadCount > 0)
                            <button type="button" data-notifications-mark-all class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                                {{ __('Mark all read') }}
                            </button>
                        @endif
                    </div>
                    <div data-notifications-list class="max-h-96 overflow-y-auto" data-url="{{ route('notifications.index') }}">
                        <div class="px-4 py-8 text-center text-sm text-slate-500">{{ __('Loading...') }}</div>
                    </div>
                    <div class="border-t border-slate-100 px-4 py-2.5 text-center">
                        <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">{{ __('View all') }}</a>
                    </div>
                </div>
            </div>

            <div class="relative ml-1" data-user-menu-root>
                <button type="button" data-user-menu-toggle aria-haspopup="true" aria-expanded="false"
                    class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white py-1.5 pl-1.5 pr-3 text-sm transition hover:bg-slate-50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">{{ $initials }}</span>
                    <span class="hidden max-w-[8rem] truncate font-medium text-slate-800 lg:inline">{{ $user->name }}</span>
                    <svg class="hidden h-4 w-4 text-slate-400 lg:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div data-user-menu-panel class="absolute right-0 z-[100] mt-2 hidden w-56 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl" role="menu">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                        <x-badge variant="brand" class="mt-2">{{ ucfirst($roleName) }}</x-badge>
                    </div>
                    <a href="{{ route('dashboard.settings') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50" role="menuitem">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        {{ __('Settings') }}
                    </a>
                    <form method="post" action="{{ route('logout') }}" role="none">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50" role="menuitem">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</header>
