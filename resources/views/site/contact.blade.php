@extends('layouts.app')

@section('title', ($content->title ?? site_ui('contact_page.title_fallback')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    @php
        $c = is_array($content->content ?? null) ? $content->content : [];
        $emergency = $c['emergency_contacts'] ?? [];
    @endphp

    @if(session('contact_success'))
    <div id="contact-success-modal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="contact-success-title">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-contact-modal-close></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white p-8 text-center shadow-2xl" style="animation: modal-in 0.3s ease-out">
            <button type="button" data-contact-modal-close class="absolute right-3 top-3 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 id="contact-success-title" class="mt-5 text-xl font-bold text-slate-900">{{ __('Thank You!') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ session('contact_success') }}</p>
            <button type="button" data-contact-modal-close class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700 hover:shadow-xl">
                {{ __('OK') }}
            </button>
        </div>
    </div>
    @endif

    <div class="bg-white">
        @if($siteSettings->section_visibility['contact_hero'] ?? true)
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold md:text-5xl">{{ $content->title ?? site_ui('contact_page.title_fallback') }}</h1>
                @if($content->meta_description ?? false)
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ $content->meta_description }}</p>
                @endif
            </div>
        </div>
        @endif

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            {{-- Contact cards row --}}
            @if($siteSettings->section_visibility['contact_cards'] ?? true)
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 reveal">
                <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.037 11.037 0 006.105 6.105l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ __('Phone') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $siteSettings->phone ?? site_ui('footer.phone') }}</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ __('Email') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $siteSettings->email ?? site_ui('footer.email') }}</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ __('Address') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $siteSettings->full_address ?? $siteSettings->address ?? site_ui('footer.address') }}</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ __('Hours') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('Sun–Thu, 8 AM – 2 PM') }}</p>
                </div>
            </div>
            @endif

            {{-- Main form + info split --}}
            @if($siteSettings->section_visibility['contact_form'] ?? true)
            <div class="mt-12 grid gap-12 lg:grid-cols-2 reveal">
                {{-- Left: Contact form --}}
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">{{ site_ui('contact_page.form_heading') }}</h2>
                    <div class="mt-2 h-1 w-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                    <form method="post" action="{{ route('site.contact.store') }}" class="mt-8 space-y-5">
                        @csrf
                        <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ site_ui('contact_page.name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('name') border-red-300 bg-red-50 @enderror">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ site_ui('contact_page.email') }} <span class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('email') border-red-300 bg-red-50 @enderror">
                                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ site_ui('contact_page.phone') }}</label>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ site_ui('contact_page.subject') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="subject" value="{{ old('subject') }}" required
                                class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('subject') border-red-300 bg-red-50 @enderror">
                            @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ site_ui('contact_page.message') }} <span class="text-red-500">*</span></label>
                            <textarea name="message" rows="5" required
                                class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('message') border-red-300 bg-red-50 @enderror">{{ old('message') }}</textarea>
                            @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700 hover:shadow-xl">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ site_ui('contact_page.send') }}
                        </button>
                    </form>
                </div>

                {{-- Right: Info + Map --}}
                <div>
                    {{-- Opening hours --}}
                    @if(($siteSettings->section_visibility['contact_hours'] ?? true) && $siteSettings?->opening_hours && is_array($siteSettings->opening_hours))
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-6">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">{{ site_ui('contact_page.location_hours') }}</h3>
                            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                                @foreach ($siteSettings->opening_hours as $day => $hours)
                                    <li class="flex justify-between">
                                        <span class="font-medium capitalize text-slate-800">{{ $day }}</span>
                                        @if(!empty($hours['open']) && !empty($hours['close']))
                                            <span>{{ $hours['open'] }} – {{ $hours['close'] }}</span>
                                        @else
                                            <span class="text-slate-400">{{ site_ui('contact_page.closed') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Emergency contacts --}}
                    @if(($siteSettings->section_visibility['contact_emergency'] ?? true) && count($emergency))
                        <div class="mt-6 rounded-2xl border border-red-100 bg-red-50 p-6">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-red-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                {{ site_ui('contact_page.emergency_contacts') }}
                            </h3>
                            <ul class="mt-3 space-y-2">
                                @foreach ($emergency as $row)
                                    <li class="flex items-center justify-between text-sm">
                                        <span class="text-red-700">{{ $row['label'] ?? '' }}</span>
                                        <a href="tel:{{ preg_replace('/\s+/', '', $row['phone'] ?? '') }}" class="font-semibold text-red-800 hover:underline">{{ $row['phone'] ?? '' }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Map --}}
                    @if($siteSettings->section_visibility['contact_map'] ?? true)
                    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm">
                        @if(config('school.google_maps_embed_url'))
                            <iframe title="{{ site_ui('contact_page.map_iframe_title') }}" src="{{ config('school.google_maps_embed_url') }}" class="h-72 w-full border-0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
                        @else
                            @php $addr = urlencode(trim(($siteSettings->full_address ?? '') ?: ($siteSettings->address ?? 'school'))); @endphp
                            <div class="flex h-72 items-center justify-center bg-slate-200 p-6 text-center">
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $addr }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-blue-700" rel="noopener noreferrer" target="_blank">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ site_ui('contact_page.open_in_maps') }}
                                </a>
                            </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- FAQ Accordion --}}
            @if($siteSettings->section_visibility['contact_faq'] ?? true)
            <section class="mt-16 reveal">
                <h2 class="text-2xl font-bold text-center text-slate-900">{{ __('Frequently Asked Questions') }}</h2>
                <div class="mx-auto mt-8 max-w-3xl space-y-3">
                    <details class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                            {{ __('What are the school hours?') }}
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ __('School operates Sunday through Thursday, 8:00 AM to 2:00 PM. Office hours are 8:00 AM to 4:00 PM.') }}</p>
                    </details>
                    <details class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                            {{ __('How can I apply for admission?') }}
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ __('You can apply online through our website or visit the school office during working hours for a paper application.') }}</p>
                    </details>
                    <details class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                            {{ __('What documents are required for admission?') }}
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ __('Birth certificate, previous school transfer certificate, passport-size photographs, and guardian ID card.') }}</p>
                    </details>
                </div>
            </section>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function(){
    var modal = document.getElementById('contact-success-modal');
    if (!modal) return;
    function closeModal() { modal.remove(); }
    modal.querySelectorAll('[data-contact-modal-close]').forEach(function(el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
})();
</script>
@endpush
