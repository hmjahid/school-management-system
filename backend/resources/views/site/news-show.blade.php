@extends('layouts.app')

@section('title', $article->title . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'datePublished' => optional($article->published_at)->toAtomString(),
            'dateModified' => optional($article->updated_at)->toAtomString(),
            'author' => [
                '@type' => 'Person',
                'name' => $article->author_name ?: ($siteSettings->school_name ?? config('app.name')),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteSettings->school_name ?? config('app.name'),
            ],
            'image' => $article->image_url ? [$article->image_url] : null,
            'mainEntityOfPage' => url()->current(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $article->title,
            'subtitle' => $article->published_at ? $article->published_at->format('F j, Y') : null,
        ])
        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('site.news') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ site_ui('news_show.back') }}</a>
            @if($article->image_url)
                <img src="{{ $article->image_url }}" alt="{{ $article->title }}" loading="lazy" class="mt-6 w-full rounded-xl border border-gray-200 object-cover shadow-sm">
            @endif
            <div class="mt-8 max-w-none text-base leading-relaxed text-gray-700">
                {!! nl2br(e($article->content)) !!}
            </div>
        </div>
    </div>
@endsection
