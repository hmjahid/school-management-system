@extends('layouts.app')

@section('title', ($content->title ?? site_ui('nav.committee')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @if($siteSettings->section_visibility['page_hero'] ?? true)
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? site_ui('nav.committee'),
            'subtitle' => $content->meta_description,
        ])
        @endif

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            {{-- Intro --}}
            @php
                $payload = $content->localizedPayload();
                $intro = $payload['intro'] ?? null;
                $sections = $payload['sections'] ?? null;
            @endphp

            @if($intro)
                <div class="mb-12 max-w-3xl text-gray-600 leading-relaxed">{!! nl2br(e($intro)) !!}</div>
            @endif

            {{-- Committee Members Grid --}}
            @if($members->isNotEmpty())
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($members as $member)
                        @php
                            $name = $member->localizedName();
                            $designation = $member->localizedDesignation();
                            $bio = $member->localizedBio();
                            $photo = $member->photo_url;
                            $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
                        @endphp
                        <div class="group rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 text-center transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-3xl font-bold text-blue-600 ring-4 ring-white shadow-lg transition-transform duration-300 group-hover:scale-105 overflow-hidden">
                                @if($photo)
                                    <img src="{{ $photo }}" alt="{{ $name }}" class="h-full w-full object-cover">
                                @else
                                    {{ $initials }}
                                @endif
                            </div>
                            <h3 class="mt-4 text-xl font-semibold text-gray-900">{{ $name }}</h3>
                            <p class="mt-1 text-sm font-medium text-blue-600">{{ $designation }}</p>
                            @if($bio)
                                <p class="mt-3 text-sm text-gray-500 leading-relaxed">{{ \Illuminate\Support\Str::limit($bio, 150) }}</p>
                            @endif
                            <div class="mt-4 flex items-center justify-center gap-3">
                                @if($member->phone)
                                    <a href="tel:{{ $member->phone }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="{{ $member->phone }}">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                                    </a>
                                @endif
                                @if($member->email)
                                    <a href="mailto:{{ $member->email }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="{{ $member->email }}">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-16 text-center text-gray-500">
                    <svg class="mx-auto h-16 w-16 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                    <p class="mt-4 text-lg font-medium text-gray-700">{{ __('No committee members added yet.') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Committee members will appear here once added in the dashboard.') }}</p>
                </div>
            @endif

            {{-- CMS Sections --}}
            @if($sections)
                @foreach($sections as $section)
                    @if(!empty($section['heading']))
                        <div class="mt-16">
                            <h2 class="mb-4 text-2xl font-bold text-gray-900">{{ $section['heading'] }}</h2>
                            <div class="mx-auto h-1 w-16 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                        </div>
                    @endif
                    @if(!empty($section['paragraphs']))
                        <div class="mt-4 max-w-3xl space-y-4 text-gray-600 leading-relaxed">
                            @foreach($section['paragraphs'] as $p)
                                <p>{!! nl2br(e($p)) !!}</p>
                            @endforeach
                        </div>
                    @endif
                    @if(!empty($section['bullets']))
                        <ul class="mt-4 max-w-3xl space-y-2">
                            @foreach($section['bullets'] as $bullet)
                                <li class="flex items-start gap-2 text-gray-600">
                                    <svg class="mt-1 h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ $bullet }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
@endsection
