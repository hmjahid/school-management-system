@extends('layouts.app')

@section('title', ($content->title ?? site_ui('nav.faculty')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? site_ui('nav.faculty'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            <section class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900">{{ site_ui('faculty_page.directory_heading') }}</h2>
                <div class="mt-2 h-1 w-20 bg-orange-500"></div>
                @if($teachers->isEmpty())
                    <p class="mt-4 text-gray-600">{{ site_ui('faculty_page.empty') }}</p>
                @else
                    <ul class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($teachers as $teacher)
                            <li class="rounded-lg border border-gray-200 bg-white p-6 shadow-md transition-shadow hover:shadow-lg">
                                <p class="font-semibold text-gray-900">{{ $teacher->user?->name ?? site_ui('faculty_page.staff_fallback') }}</p>
                                @if($teacher->qualification)
                                    <p class="mt-2 text-sm text-gray-600">{{ $teacher->qualification }}</p>
                                @endif
                                @if($teacher->phone)
                                    <p class="mt-3 text-xs text-gray-500">{{ $teacher->phone }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
