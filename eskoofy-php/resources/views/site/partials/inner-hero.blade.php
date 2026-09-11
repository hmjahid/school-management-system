@php
    $t = $title ?? '';
    $st = $subtitle ?? null;
@endphp
<div class="relative bg-gray-900 text-white">
    <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-blue-800 opacity-95"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $t }}</h1>
        @if($st)
            <p class="mt-3 max-w-3xl text-lg text-blue-100">{{ $st }}</p>
        @endif
    </div>
</div>
