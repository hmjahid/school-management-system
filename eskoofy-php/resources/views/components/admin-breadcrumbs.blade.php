@props([
    'items' => [],
])

@if (count($items) > 0)
    <nav class="mb-3 flex" aria-label="{{ __('Breadcrumb') }}">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm">
            @foreach ($items as $i => $item)
                <li class="flex items-center gap-1.5">
                    @if ($i > 0)
                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/>
                        </svg>
                    @endif
                    @if (!empty($item['url']) && $i < count($items) - 1)
                        <a href="{{ $item['url'] }}" class="rounded-md px-1 py-0.5 text-slate-500 transition hover:text-brand-600 hover:underline">{{ $item['label'] }}</a>
                    @else
                        <span class="rounded-md px-1 py-0.5 font-medium text-slate-800" aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
