@props([
    'href',
    'routeIs' => null,
    'icon' => null,
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
</a>
