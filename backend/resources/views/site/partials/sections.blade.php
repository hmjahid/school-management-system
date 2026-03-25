@php
    $c = is_array($content->content ?? null) ? $content->content : [];
    $sections = $c['sections'] ?? [];
@endphp
@if(!empty($c['intro']))
    <p class="mb-8 max-w-3xl text-lg leading-relaxed text-gray-600">{{ $c['intro'] }}</p>
@endif

@foreach ($sections as $section)
    <section class="mb-12 scroll-mt-24" @if(!empty($section['id'])) id="{{ $section['id'] }}" @endif>
        @if(!empty($section['heading']))
            <h2 class="text-2xl font-bold text-gray-900">{{ $section['heading'] }}</h2>
            <div class="mt-2 h-1 w-20 bg-blue-600"></div>
        @endif
        @foreach ($section['paragraphs'] ?? [] as $paragraph)
            <p class="mt-4 max-w-3xl leading-relaxed text-gray-600">{{ $paragraph }}</p>
        @endforeach
        @if(!empty($section['bullets']))
            <ul class="mt-4 list-inside list-disc space-y-2 text-gray-600">
                @foreach ($section['bullets'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
        @if(!empty($section['cards']))
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($section['cards'] as $card)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 shadow-sm">
                        @if(!empty($card['title']))
                            <h3 class="font-semibold text-gray-900">{{ $card['title'] }}</h3>
                        @endif
                        @if(!empty($card['body']))
                            <p class="mt-2 text-sm text-gray-600">{{ $card['body'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
        @if(!empty($section['faq']))
            <dl class="mt-4 space-y-4">
                @foreach ($section['faq'] as $row)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="font-medium text-gray-900">{{ $row['q'] ?? '' }}</dt>
                        <dd class="mt-2 text-sm text-gray-600">{{ $row['a'] ?? '' }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </section>
@endforeach
