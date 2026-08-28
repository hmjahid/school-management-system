@props([
    'href',
    'routeIs' => null,
    'icon' => null,
    'badge' => null,
])

@php
    $active = $routeIs ? request()->routeIs($routeIs) : false;
    $classes = 'admin-nav-link'.($active ? ' admin-nav-link--active' : '');
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)
        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-current opacity-80">{!! $icon !!}</span>
    @endif
    <span class="truncate">{{ $slot }}</span>
    @if ($badge)
        <span class="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ $badge }}</span>
    @endif
</a>