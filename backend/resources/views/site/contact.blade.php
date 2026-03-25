@extends('layouts.app')

@section('title', ($content->title ?? site_ui('contact_page.title_fallback')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    @php
        $c = is_array($content->content ?? null) ? $content->content : [];
        $emergency = $c['emergency_contacts'] ?? [];
    @endphp
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? site_ui('contact_page.title_fallback'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('site.partials.sections', ['content' => $content])

    <div class="mt-10 grid gap-10 lg:grid-cols-2">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('contact_page.form_heading') }}</h2>
            <form method="post" action="{{ route('site.contact.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.subject') }}</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.message') }}</label>
                    <textarea name="message" rows="5" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ site_ui('contact_page.send') }}</button>
            </form>

            <h2 class="mt-10 text-lg font-semibold text-gray-900">{{ site_ui('contact_page.feedback_heading') }}</h2>
            <form method="post" action="{{ route('site.feedback.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.your_feedback') }}</label>
                    <textarea name="message" rows="4" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ site_ui('contact_page.submit_feedback') }}</button>
            </form>

            <h2 class="mt-10 text-lg font-semibold text-gray-900">{{ site_ui('contact_page.complaint_heading') }}</h2>
            <form method="post" action="{{ route('site.complaint.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ site_ui('contact_page.details') }}</label>
                    <textarea name="message" rows="5" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-900 hover:bg-red-100">{{ site_ui('contact_page.submit_complaint') }}</button>
            </form>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('contact_page.location_hours') }}</h2>
            @if($siteSettings && ($siteSettings->full_address ?? $siteSettings->address))
                <p class="mt-2 text-sm text-gray-600">{{ $siteSettings->full_address ?? $siteSettings->address }}</p>
            @endif
            @if($siteSettings?->opening_hours && is_array($siteSettings->opening_hours))
                <ul class="mt-4 space-y-1 text-sm text-gray-600">
                    @foreach ($siteSettings->opening_hours as $day => $hours)
                        <li><span class="font-medium capitalize text-gray-800">{{ $day }}</span>
                            @if(!empty($hours['open']) && !empty($hours['close']))
                                {{ $hours['open'] }} – {{ $hours['close'] }}
                            @else
                                {{ site_ui('contact_page.closed') }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(count($emergency))
                <h3 class="mt-8 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ site_ui('contact_page.emergency_contacts') }}</h3>
                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                    @foreach ($emergency as $row)
                        <li>{{ $row['label'] ?? '' }}: <a href="tel:{{ preg_replace('/\s+/', '', $row['phone'] ?? '') }}" class="text-blue-600 hover:underline">{{ $row['phone'] ?? '' }}</a></li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-8 aspect-video w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-100">
                @if(config('school.google_maps_embed_url'))
                    <iframe title="{{ site_ui('contact_page.map_iframe_title') }}" src="{{ config('school.google_maps_embed_url') }}" class="h-full w-full border-0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
                @else
                    @php
                        $addr = urlencode(trim(($siteSettings->full_address ?? '') ?: ($siteSettings->address ?? 'school')));
                    @endphp
                    <div class="flex h-full items-center justify-center p-6 text-center">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $addr }}" class="text-sm font-medium text-blue-600 hover:underline" rel="noopener noreferrer" target="_blank">{{ site_ui('contact_page.open_in_maps') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
        </div>
    </div>
@endsection
