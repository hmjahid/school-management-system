@extends('layouts.app')

@section('title', ($content->title ?? __('Gallery')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? __('Gallery'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('site.partials.sections', ['content' => $content])

    @foreach ($items as $category => $group)
        <section class="mt-12">
            <h2 class="text-lg font-semibold text-gray-900">{{ $category }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($group as $g)
                    @php
                        $src = $g->image_path;
                        if ($src && ! \Illuminate\Support\Str::startsWith($src, ['http://', 'https://'])) {
                            $src = \Illuminate\Support\Facades\Storage::url($src);
                        }
                    @endphp
                    <figure class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        @if($src)
                            <img src="{{ $src }}" alt="{{ $g->title }}" class="aspect-[4/3] w-full object-cover" loading="lazy">
                        @endif
                        <figcaption class="p-4">
                            <p class="font-medium text-gray-900">{{ $g->title }}</p>
                            @if($g->description)
                                <p class="mt-1 text-sm text-gray-600">{{ $g->description }}</p>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endforeach

    @if($items->isEmpty())
        <p class="mt-8 text-sm text-gray-600">{{ __('Gallery images will appear here when published.') }}</p>
    @endif

    <section class="mt-12 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">
        <h2 class="font-semibold text-gray-900">{{ __('Video gallery & virtual tour') }}</h2>
        <p class="mt-2">{{ __('Embed video links or a 360° tour URL in your CMS “gallery” page content, or host files and link them from the structured sections.') }}</p>
    </section>
        </div>
    </div>
@endsection
