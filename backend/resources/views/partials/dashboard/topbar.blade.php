@php
    $user = auth()->user();
@endphp

<header class="flex h-16 flex-shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 shadow-sm md:px-6">
    <div class="flex items-center gap-3">
        <label for="dashboard-drawer" class="inline-flex cursor-pointer items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden" aria-label="{{ __('Open menu') }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </label>
        <div class="hidden min-w-0 sm:block">
            <p class="truncate text-sm text-gray-500">{{ __('Signed in as') }}</p>
            <p class="truncate font-semibold text-gray-900">{{ $user->name }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('home') }}" class="hidden rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 sm:inline-block">{{ __('Public site') }}</a>
        <span class="hidden rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 sm:inline-block">
            {{ $user->getRoleNames()->first() ?? __('User') }}
        </span>
    </div>
</header>
