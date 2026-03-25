@extends('layouts.dashboard')

@section('title', __('Edit page') . ': ' . $page . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.cms.pages') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← {{ __('Back to pages') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ __('Edit') }}: <span class="font-mono">{{ $page }}</span></h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('Edit English and Bengali copy. The public site shows the version that matches the visitor’s language (Bengali falls back to English for missing keys).') }}</p>
    </div>

    @if ($page === 'site-ui')
        @include('dashboard.cms.partials.site-ui-help')
    @endif

    @php
        $inputMode = old('cms_input_mode', $content->cms_input_mode ?? \App\Models\WebsiteContent::INPUT_MODE_JSON);
        if (! $allowFormMode) {
            $inputMode = \App\Models\WebsiteContent::INPUT_MODE_JSON;
        }
    @endphp

    <form method="post" action="{{ route('dashboard.cms.update', ['page' => $page]) }}" class="max-w-5xl space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm" id="cms-page-form">
        @csrf
        @method('PUT')

        <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 p-4">
            <p class="text-sm font-medium text-indigo-900">{{ __('Input mode') }}</p>
            <p class="mt-1 text-xs text-indigo-800/90">{{ __('Choose JSON for full control (nested blocks, site-ui keys) or form fields for intro, sections, and home hero.') }}</p>
            <div class="mt-3 flex flex-wrap gap-4">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-800">
                    <input type="radio" name="cms_input_mode" value="json" class="size-4 border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ $inputMode === 'json' ? 'checked' : '' }} data-mode-toggle="json" @if(! $allowFormMode) checked @endif>
                    {{ __('JSON editor') }}
                </label>
                @if ($allowFormMode)
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-800">
                        <input type="radio" name="cms_input_mode" value="form" class="size-4 border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ $inputMode === 'form' ? 'checked' : '' }} data-mode-toggle="form">
                        {{ __('Form fields') }}
                    </label>
                @else
                    <p class="text-xs text-gray-600">{{ __('The site-ui page must be edited as JSON (global key overrides).') }}</p>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Page title (English)') }} <span class="text-red-500">*</span></label>
                <input type="text" name="title_en" value="{{ old('title_en', $content->title_en ?? $content->title) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Page title (Bengali)') }}</label>
                <input type="text" name="title_bn" value="{{ old('title_bn', $content->title_bn ?? $content->title_en ?? $content->title) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta description (English)') }}</label>
                <input type="text" name="meta_description_en" value="{{ old('meta_description_en', $content->meta_description_en ?? $content->meta_description) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta description (Bengali)') }}</label>
                <input type="text" name="meta_description_bn" value="{{ old('meta_description_bn', $content->meta_description_bn ?? $content->meta_description_en ?? $content->meta_description) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta keywords') }}</label>
            <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $content->meta_keywords) }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>

        {{-- JSON panels --}}
        <div id="cms-panel-json" class="cms-mode-panel space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Content JSON (English)') }}</label>
                <textarea name="content_json_en" rows="16" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('content_json_en', $contentJsonEn) }}</textarea>
                @error('content_json_en')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Content JSON (Bengali)') }}</label>
                <p class="mb-1 text-xs text-gray-500">{{ __('Leave empty to reuse English until you add a Bengali tree.') }}</p>
                <textarea name="content_json_bn" rows="16" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('content_json_bn', $contentJsonBn) }}</textarea>
                @error('content_json_bn')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Form panels --}}
        @if ($allowFormMode)
            <div id="cms-panel-form" class="cms-mode-panel hidden space-y-6 border-t border-gray-100 pt-6">
                <p class="text-sm text-gray-600">{{ __('Form mode updates intro, sections, and (on the home page) hero. Other JSON keys are kept when you save from the form.') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Intro (English)') }}</label>
                        <textarea name="form_intro_en" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('form_intro_en', $formIntroEn) }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Intro (Bengali)') }}</label>
                        <textarea name="form_intro_bn" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('form_intro_bn', $formIntroBn) }}</textarea>
                    </div>
                </div>

                @if ($page === 'home')
                    <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4">
                        <h3 class="text-sm font-semibold text-amber-900">{{ __('Home hero') }}</h3>
                        <p class="mt-1 text-xs text-amber-900/80">{{ __('Background image URL is shared for both languages.') }}</p>
                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Background image URL') }}</label>
                            <input type="url" name="form_hero_background" value="{{ old('form_hero_background', $formHeroBackground) }}" placeholder="https://…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Headline (English)') }}</label>
                                <input type="text" name="form_hero_headline_en" value="{{ old('form_hero_headline_en', $formHeroHeadlineEn) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Headline (Bengali)') }}</label>
                                <input type="text" name="form_hero_headline_bn" value="{{ old('form_hero_headline_bn', $formHeroHeadlineBn) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Subtitle / motto (English)') }}</label>
                                <input type="text" name="form_hero_subtitle_en" value="{{ old('form_hero_subtitle_en', $formHeroSubtitleEn) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Subtitle / motto (Bengali)') }}</label>
                                <input type="text" name="form_hero_subtitle_bn" value="{{ old('form_hero_subtitle_bn', $formHeroSubtitleBn) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                        </div>
                    </div>
                @endif

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('Sections') }}</h3>
                        <button type="button" id="cms-add-section" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">{{ __('Add section') }}</button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Each section: heading and body (English / Bengali). Body can use blank lines between paragraphs.') }}</p>

                    <div id="cms-sections-wrap" class="mt-4 space-y-4">
                        @foreach ($formSections as $idx => $row)
                            <div class="cms-section-row rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="mb-2 flex justify-end">
                                    <button type="button" class="cms-remove-section text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Heading (English)') }}</label>
                                        <input type="text" name="form_sections[{{ $idx }}][heading_en]" value="{{ $row['heading_en'] ?? '' }}" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Heading (Bengali)') }}</label>
                                        <input type="text" name="form_sections[{{ $idx }}][heading_bn]" value="{{ $row['heading_bn'] ?? '' }}" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Body (English)') }}</label>
                                        <textarea name="form_sections[{{ $idx }}][body_en]" rows="4" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">{{ $row['body_en'] ?? '' }}</textarea>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Body (Bengali)') }}</label>
                                        <textarea name="form_sections[{{ $idx }}][body_bn]" rows="4" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">{{ $row['body_bn'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <template id="cms-section-template">
            <div class="cms-section-row rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="mb-2 flex justify-end">
                    <button type="button" class="cms-remove-section text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Heading (English)') }}</label>
                        <input type="text" name="form_sections[__INDEX__][heading_en]" value="" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Heading (Bengali)') }}</label>
                        <input type="text" name="form_sections[__INDEX__][heading_bn]" value="" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Body (English)') }}</label>
                        <textarea name="form_sections[__INDEX__][body_en]" rows="4" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Body (Bengali)') }}</label>
                        <textarea name="form_sections[__INDEX__][body_bn]" rows="4" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($errors->any() ? request()->has('is_active') : ($content->is_active ?? true))>
            <label for="is_active" class="text-sm text-gray-700">{{ __('Page is active') }}</label>
        </div>

        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Save page') }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            const form = document.getElementById('cms-page-form');
            if (!form) return;

            const panelJson = document.getElementById('cms-panel-json');
            const panelForm = document.getElementById('cms-panel-form');
            const radios = form.querySelectorAll('[data-mode-toggle]');

            function syncMode() {
                const v = form.querySelector('input[name="cms_input_mode"]:checked')?.value || 'json';
                if (panelJson) panelJson.classList.toggle('hidden', v !== 'json');
                if (panelForm) panelForm.classList.toggle('hidden', v !== 'form');
                const taEn = form.querySelector('textarea[name="content_json_en"]');
                const taBn = form.querySelector('textarea[name="content_json_bn"]');
                if (taEn) taEn.required = v === 'json';
                if (taBn) taBn.required = false;
            }

            radios.forEach(function (r) { r.addEventListener('change', syncMode); });
            syncMode();

            const wrap = document.getElementById('cms-sections-wrap');
            const tpl = document.getElementById('cms-section-template');
            const addBtn = document.getElementById('cms-add-section');

            function nextIndex() {
                if (!wrap) return 0;
                const inputs = wrap.querySelectorAll('input[name^="form_sections["]');
                let max = -1;
                inputs.forEach(function (el) {
                    const m = el.name.match(/^form_sections\[(\d+)]/);
                    if (m) max = Math.max(max, parseInt(m[1], 10));
                });
                return max + 1;
            }

            if (addBtn && wrap && tpl) {
                addBtn.addEventListener('click', function () {
                    const i = nextIndex();
                    const html = tpl.innerHTML.replace(/__INDEX__/g, String(i));
                    const div = document.createElement('div');
                    div.innerHTML = html.trim();
                    const node = div.firstElementChild;
                    if (node) wrap.appendChild(node);
                });
            }

            if (wrap) {
                wrap.addEventListener('click', function (e) {
                    const t = e.target;
                    if (t && t.classList && t.classList.contains('cms-remove-section')) {
                        const row = t.closest('.cms-section-row');
                        if (row && wrap.querySelectorAll('.cms-section-row').length > 1) row.remove();
                    }
                });
            }
        })();
    </script>
@endsection
