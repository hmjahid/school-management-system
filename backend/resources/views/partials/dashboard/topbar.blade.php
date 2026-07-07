@php
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
@endphp

<header class="flex h-16 flex-shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 shadow-sm md:px-6 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-center gap-3">
        <label for="dashboard-drawer" class="inline-flex cursor-pointer items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden dark:text-gray-300 dark:hover:bg-gray-700" aria-label="{{ __('Open menu') }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </label>
        <div class="hidden min-w-0 sm:block">
            <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ __('Signed in as') }}</p>
            <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="hidden rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 sm:inline-block dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Public site') }}</a>
        <span class="hidden rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 sm:inline-block dark:bg-blue-900 dark:text-blue-200">
            {{ $user->getRoleNames()->first() ?? __('User') }}
        </span>

        @auth
            <div class="relative" data-notifications-root>
                <button type="button" data-notifications-toggle aria-label="{{ __('Notifications') }}" aria-haspopup="true" aria-expanded="false"
                    class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if ($unreadCount > 0)
                        <span data-notifications-badge class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[0.65rem] font-bold leading-none text-white">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    @endif
                </button>
                <div data-notifications-panel class="absolute right-0 z-50 mt-2 hidden w-80 max-w-[90vw] origin-top-right rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800" role="menu">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-2 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Notifications') }}</p>
                        @if ($unreadCount > 0)
                            <button type="button" data-notifications-mark-all class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                {{ __('Mark all read') }}
                            </button>
                        @endif
                    </div>
                    <div data-notifications-list class="max-h-96 overflow-y-auto" data-url="{{ route('notifications.index') }}">
                        <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Loading...') }}</div>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-2 text-center dark:border-gray-700">
                        <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">{{ __('View all') }}</a>
                    </div>
                </div>
            </div>
        @endauth
    </div>
</header>
