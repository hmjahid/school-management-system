@props([
    'title' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'admin-card']) }}>
    @if ($title || isset($header))
        <div class="admin-card-header">
            @if ($title)
                <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
            @endif
            @isset($header)
                {{ $header }}
            @endisset
        </div>
    @endif
    <div @class(['admin-card-body' => $padding])>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="border-t border-slate-100 px-5 py-3">{{ $footer }}</div>
    @endisset
</div>
