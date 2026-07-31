@extends('layouts.dashboard')

@section('title', __('About Page') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.cms.pages') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← {{ __('Back to pages') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ __('About Page') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Edit the public About page content. Sections appear on the public site.') }}</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('dashboard.settings.update.about') }}" class="space-y-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Page Meta --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-gray-900">{{ __('Page settings') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Title') }} (EN)</label>
                    <input type="text" name="title_en" value="{{ old('title_en', $content->title_en ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Title') }} (বাংলা)</label>
                    <input type="text" name="title_bn" value="{{ old('title_bn', $content->title_bn ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none">
                </div>
            </div>
            <label class="mt-4 inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_active', $content->is_active ?? true))>
                {{ __('Page is active') }}
            </label>
        </div>

        {{-- Page Introduction --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-1 text-base font-semibold text-gray-900">{{ __('Page introduction') }}</h2>
            <p class="mb-4 text-xs text-gray-500">{{ __('Short intro text shown at the top of the About page.') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Introduction') }} (EN)</label>
                    <textarea name="intro_en" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none">{{ old('intro_en', $values['en']['intro'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Introduction') }} (বাংলা)</label>
                    <textarea name="intro_bn" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none">{{ old('intro_bn', $values['bn']['intro'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Content Sections --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">{{ __('Content sections') }}</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Each section has a heading and body text. Separate paragraphs with blank lines.') }}</p>
                </div>
                <button type="button" data-cms-rep-add="about-sections" data-name="sections" class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add section') }}
                </button>
            </div>

            <div class="space-y-4" data-cms-repeater="about-sections" data-cms-repeater-name="sections">
                @php
                    $rowsEn = $values['en']['sections'] ?? [];
                    $rowsBn = $values['bn']['sections'] ?? [];
                @endphp
                @forelse ($rowsEn as $idx => $rowEn)
                    @php
                        $rowBn = $rowsBn[$idx] ?? [];
                        $parasEn = is_array($rowEn['paragraphs'] ?? null) ? implode("\n\n", $rowEn['paragraphs']) : ($rowEn['paragraphs'] ?? '');
                        $parasBn = is_array($rowBn['paragraphs'] ?? null) ? implode("\n\n", $rowBn['paragraphs']) : ($rowBn['paragraphs'] ?? '');
                    @endphp
                    <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Section') }} #{{ $idx + 1 }}</span>
                            <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Heading') }} (EN)</label>
                                <input type="text" name="sections[{{ $idx }}][heading_en]" value="{{ $rowEn['heading'] ?? '' }}" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Heading') }} (বাংলা)</label>
                                <input type="text" name="sections[{{ $idx }}][heading_bn]" value="{{ $rowBn['heading'] ?? '' }}" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Body') }} (EN) <span class="text-gray-400">— {{ __('one paragraph per blank line') }}</span></label>
                                <textarea name="sections[{{ $idx }}][paragraphs_en]" rows="5" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">{{ $parasEn }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Body') }} (বাংলা) <span class="text-gray-400">— {{ __('one paragraph per blank line') }}</span></label>
                                <textarea name="sections[{{ $idx }}][paragraphs_bn]" rows="5" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">{{ $parasBn }}</textarea>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">{{ __('No sections yet. Click "Add section" to create one.') }}</p>
                @endforelse
            </div>

            <template data-cms-rep-template="about-sections">
                <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="cms-rep-label text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Section') }} #__INDEX__</span>
                        <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Heading') }} (EN)</label>
                            <input type="text" name="__NAME__[__INDEX__][heading_en]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Heading') }} (বাংলা)</label>
                            <input type="text" name="__NAME__[__INDEX__][heading_bn]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Body') }} (EN) <span class="text-gray-400">— {{ __('one paragraph per blank line') }}</span></label>
                            <textarea name="__NAME__[__INDEX__][paragraphs_en]" rows="5" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Body') }} (বাংলা) <span class="text-gray-400">— {{ __('one paragraph per blank line') }}</span></label>
                            <textarea name="__NAME__[__INDEX__][paragraphs_bn]" rows="5" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Preview + Save --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <a href="{{ route('site.about') }}" target="_blank" rel="noopener" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                {{ __('Preview public page') }} ↗
            </a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                {{ __('Save about page') }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            document.addEventListener('click', function (e) {
                var t = e.target;
                if (!t) return;
                if (t.closest('[data-cms-rep-add]')) {
                    var btn = t.closest('[data-cms-rep-add]');
                    var id = btn.getAttribute('data-cms-rep-add');
                    var name = btn.getAttribute('data-name');
                    var tpl = document.querySelector('template[data-cms-rep-template="' + id + '"]');
                    var wrap = document.querySelector('[data-cms-repeater="' + id + '"]');
                    if (!tpl || !wrap) return;
                    var items = wrap.querySelectorAll('.cms-rep-row');
                    var nextIdx = items.length;
                    var html = tpl.innerHTML
                        .replace(/__NAME__/g, name)
                        .replace(/__INDEX__/g, String(nextIdx));
                    var div = document.createElement('div');
                    div.innerHTML = html.trim();
                    var node = div.firstElementChild;
                    if (node) {
                        var placeholder = wrap.querySelector('p');
                        if (placeholder) placeholder.remove();
                        wrap.appendChild(node);
                    }
                }
                if (t.closest('[data-cms-rep-remove]')) {
                    var row = t.closest('[data-cms-rep-remove]').closest('.cms-rep-row');
                    if (row) row.remove();
                }
            });
        })();
    </script>
@endsection
