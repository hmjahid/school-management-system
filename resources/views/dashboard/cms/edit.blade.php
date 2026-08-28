@extends('layouts.dashboard')

@section('title', __('Edit page') . ': ' . ($def['label'] ?? $page) . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.cms.pages') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← {{ __('Back to pages') }}</a>
        <h1 class="mt-2 flex items-center gap-2 text-2xl font-bold text-gray-900">
            <span>{{ __('Edit') }}: {{ $def['label'] ?? $page }}</span>
            <span class="rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-medium text-gray-600">{{ $page }}</span>
        </h1>
        @if(! empty($def['description']))
            <p class="mt-1 text-sm text-gray-500">{{ $def['description'] }}</p>
        @endif
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">{{ __('Please fix the following before saving:') }}</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('dashboard.cms.update', ['page' => $page]) }}" class="space-y-6" id="cms-page-form" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('dashboard.cms.partials.page-meta-fields', ['content' => $content])

        @if(! empty($visibilityKeys))
            <details class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Section visibility') }}</span>
                    <svg class="h-5 w-5 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                    <p class="mb-4 text-xs text-slate-500">{{ __('Show or hide individual sections of this page on the public website.') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($visibilityKeys as $key => $label)
                            <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                                <input type="hidden" name="section_visibility[{{ $key }}]" value="0">
                                <input type="checkbox" name="section_visibility[{{ $key }}]" value="1"
                                    @checked(old("section_visibility.{$key}", $sectionVis[$key] ?? true))
                                    class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </details>
        @endif

        {{-- Body sections, rendered from the registry --}}
        @foreach ($def['sections'] ?? [] as $section)
            @php
                $type = $section['type'] ?? 'text';
                $key = $section['key'];
                $partialMap = [
                    'text' => 'dashboard.cms.fields.text',
                    'textarea' => 'dashboard.cms.fields.textarea',
                    'image' => 'dashboard.cms.fields.image',
                    'list' => 'dashboard.cms.fields.list',
                    'repeater' => 'dashboard.cms.fields.repeater',
                    'repeater_sections' => 'dashboard.cms.fields.repeater_sections',
                    'select' => 'dashboard.cms.fields.select',
                    'slider' => 'dashboard.cms.fields.slider',
                    'kv' => 'dashboard.cms.fields.kv',
                    'hero' => 'dashboard.cms.fields.hero',
                    'group' => 'dashboard.cms.fields.group',
                    'contact_cards' => 'dashboard.cms.fields.contact_cards',
                ];
                $partial = $partialMap[$type] ?? null;
            @endphp
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" @if($key === 'hero') data-hero-section @endif>
                <header class="mb-4">
                    <h2 class="text-base font-semibold text-gray-900">{{ $section['label'] ?? ucfirst($key) }}</h2>
                    @if(! empty($section['help']))
                        <p class="mt-1 text-xs text-gray-500">{{ $section['help'] }}</p>
                    @endif
                </header>
                @if ($partial)
                    @php
                        $formKey = str_replace('.', '_', $key);
                    @endphp
                    @include($partial, [
                        'name' => $formKey,
                        'key' => $key,
                        'field' => $section,
                        'valueEn' => old($formKey.'_en', data_get($values['en'], $key)),
                        'valueBn' => old($formKey.'_bn', data_get($values['bn'], $key)),
                        'value' => old($formKey, data_get($values['en'], $key)),
                        'dataEn' => data_get($values['en'], $key, []),
                        'dataBn' => data_get($values['bn'], $key, []),
                        'listEn' => data_get($values['en'], $key, []),
                        'listBn' => data_get($values['bn'], $key, []),
                        'rowsEn' => data_get($values['en'], $key, []),
                        'rowsBn' => data_get($values['bn'], $key, []),
                    ])
                @else
                    <p class="text-sm text-red-600">{{ __('Unsupported field type') }}: <code>{{ $type }}</code></p>
                @endif
            </section>
        @endforeach

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_active', $content->is_active ?? true))>
                {{ __('Page is active') }}
            </label>
            <div class="flex items-center gap-2">
                <button type="button" data-cms-preview-open class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                    {{ __('Preview') }}
                </button>
                <a href="{{ route('home') }}{{ $page === 'home' ? '' : '/' . $page }}" target="_blank" rel="noopener" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                    {{ __('Preview public page') }} ↗
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                    {{ __('Save page') }}
                </button>
            </div>
        </div>
    </form>

    {{-- Live preview modal --}}
    <div id="cms-preview-overlay" class="fixed inset-0 z-50 hidden flex-col bg-black/70" role="dialog" aria-modal="true" aria-label="{{ __('Page preview') }}" style="display:none;">
        <div class="mx-auto flex w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="height:90vh;">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    {{ __('Live preview') }} — {{ $def['label'] ?? $page }}
                </h3>
                <div class="flex items-center gap-2">
                    <div class="flex overflow-hidden rounded-lg border border-gray-300" data-cms-preview-lang>
                        <button type="button" data-lang="en" class="px-3 py-1 text-xs font-semibold text-white bg-indigo-600">EN</button>
                        <button type="button" data-lang="bn" class="px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">বাংলা</button>
                    </div>
                    <button type="button" data-cms-preview-close class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="{{ __('Close preview') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <iframe id="cms-preview-frame" src="" title="{{ __('Page preview') }}" class="flex-1 border-0 bg-white"></iframe>
        </div>
    </div>

    {{-- Media library browser modal (rendered once) --}}
    <div id="media-browser-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60" style="display:none;">
        <div class="relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="width:80vw;height:80vh;">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Media Library') }}</h3>
                <button type="button" onclick="closeMediaBrowser()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <iframe id="media-browser-frame" src="" class="flex-1 border-0" style="width:100%;height:100%;"></iframe>
        </div>
    </div>

        <script>
        (function () {
            // Live preview modal
            var previewOverlay = document.getElementById('cms-preview-overlay');
            var previewFrame = document.getElementById('cms-preview-frame');
            var previewOpen = document.querySelector('[data-cms-preview-open]');
            var previewClose = document.querySelector('[data-cms-preview-close]');
            var previewLang = document.querySelector('[data-cms-preview-lang]');

            if (previewOverlay && previewFrame) {
                var pageSlug = @json($page);
                var baseUrl = window.location.origin;
                var pagePath = pageSlug === 'home' ? '/' : '/' + pageSlug;
                var previewUrl = baseUrl + pagePath;

                function currentLang() {
                    return previewLang && previewLang.querySelector('button[data-lang].bg-indigo-600')
                        ? previewLang.querySelector('button[data-lang].bg-indigo-600').getAttribute('data-lang')
                        : 'en';
                }

                function setPreviewLang(lang) {
                    if (! previewLang) return;
                    previewLang.querySelectorAll('button[data-lang]').forEach(function (btn) {
                        var active = btn.getAttribute('data-lang') === lang;
                        btn.classList.toggle('bg-indigo-600', active);
                        btn.classList.toggle('text-white', active);
                        btn.classList.toggle('text-gray-600', !active);
                        btn.classList.toggle('hover:bg-gray-50', !active);
                    });
                    previewFrame.src = previewUrl + '?lang=' + lang;
                }

                if (previewOpen) {
                    previewOpen.addEventListener('click', function () {
                        previewOverlay.style.display = 'flex';
                        var lang = currentLang();
                        previewFrame.src = previewUrl + '?lang=' + lang;
                        document.body.style.overflow = 'hidden';
                    });
                }
                if (previewClose) {
                    previewClose.addEventListener('click', function () {
                        previewOverlay.style.display = 'none';
                        previewFrame.src = '';
                        document.body.style.overflow = '';
                    });
                }
                if (previewLang) {
                    previewLang.querySelectorAll('button[data-lang]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            setPreviewLang(btn.getAttribute('data-lang'));
                        });
                    });
                }
                window.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && previewOverlay.style.display === 'flex') {
                        previewClose.click();
                    }
                });
                previewOverlay.addEventListener('click', function (e) {
                    if (e.target === previewOverlay) previewClose.click();
                });
            }

            // Media library browser
            window.openMediaBrowser = function (btn) {
                var input = btn.closest('div').querySelector('input[type="url"]');
                if (! input) input = btn.parentElement.querySelector('input[type="url"]');
                var overlay = document.getElementById('media-browser-overlay');
                var iframe = document.getElementById('media-browser-frame');
                overlay.dataset.targetInput = input.id;
                iframe.src = '/dashboard/media?select=1';
                overlay.style.display = 'flex';
            };

            window.closeMediaBrowser = function () {
                var overlay = document.getElementById('media-browser-overlay');
                var iframe = document.getElementById('media-browser-frame');
                overlay.style.display = 'none';
                iframe.src = '';
            };

            window.addEventListener('message', function (e) {
                if (! e.data) return;
                if (e.data.type === 'media-selected') {
                    var overlay = document.getElementById('media-browser-overlay');
                    var inputId = overlay.dataset.targetInput;
                    if (inputId) {
                        var input = document.getElementById(inputId);
                        if (input) {
                            input.value = e.data.url;
                            var preview = input.parentElement.querySelector('img');
                            if (preview) {
                                preview.src = e.data.url;
                            } else {
                                var newImg = document.createElement('img');
                                newImg.src = e.data.url;
                                newImg.alt = '';
                                newImg.className = 'mt-2 h-24 rounded border border-gray-200 object-cover';
                                input.parentElement.appendChild(newImg);
                            }
                        }
                    }
                    closeMediaBrowser();
                } else if (e.data.type === 'media-close') {
                    closeMediaBrowser();
                }
            });

            // Hero fields visibility based on design
            var heroDesignSelect = document.getElementById('cms-hero_design');
            if (heroDesignSelect) {
                function toggleHeroFields() {
                    var design = heroDesignSelect.value;
                    var heroSection = heroDesignSelect.closest('form').querySelector('[data-hero-section]');
                    if (!heroSection) return;
                    var hideImage = design === 'design-6';
                    var imageField = null;
                    heroSection.querySelectorAll('div').forEach(function(div) {
                        var label = div.querySelector('label');
                        if (label && label.textContent.indexOf('Background image') !== -1) {
                            imageField = div;
                        }
                    });
                    if (imageField) {
                        imageField.style.display = hideImage ? 'none' : '';
                    }
                }
                heroDesignSelect.addEventListener('change', toggleHeroFields);
                toggleHeroFields();
            }

            // Repeater add buttons
            document.addEventListener('click', function (e) {
                const t = e.target;
                if (! t) return;
                if (t.closest('[data-cms-rep-add]')) {
                    const btn = t.closest('[data-cms-rep-add]');
                    const id = btn.getAttribute('data-cms-rep-add');
                    const name = btn.getAttribute('data-name');
                    const tpl = document.querySelector('template[data-cms-rep-template="' + id + '"]');
                    const wrap = document.querySelector('[data-cms-repeater="' + id + '"]');
                    if (! tpl || ! wrap) return;
                    const items = wrap.querySelectorAll('.cms-rep-row');
                    const nextIdx = items.length;
                    const html = tpl.innerHTML
                        .replace(/__NAME__/g, name)
                        .replace(/__INDEX__/g, String(nextIdx));
                    const div = document.createElement('div');
                    div.innerHTML = html.trim();
                    const node = div.firstElementChild;
                    if (node) {
                        // remove placeholder text if it's still there
                        const placeholder = wrap.querySelector('p');
                        if (placeholder) placeholder.remove();
                        wrap.appendChild(node);
                    }
                }
                if (t.closest('[data-cms-rep-remove]')) {
                    const btn = t.closest('[data-cms-rep-remove]');
                    const row = btn.closest('.cms-rep-row');
                    if (row) row.remove();
                }
                if (t.closest('[data-cms-list-add]')) {
                    const btn = t.closest('[data-cms-list-add]');
                    const id = btn.getAttribute('data-cms-list-add');
                    const name = btn.getAttribute('data-name');
                    const wrap = document.querySelector('[data-cms-list="' + id + '"]');
                    if (! wrap) return;
                    const existing = wrap.querySelectorAll('.cms-list-row');
                    const nextIdx = existing.length + 1;
                    const div = document.createElement('div');
                    div.className = 'cms-list-row grid grid-cols-12 gap-2';
                    div.innerHTML = ''
                        + '<div class="col-span-1 flex items-center text-xs text-gray-400">' + nextIdx + '</div>'
                        + '<div class="col-span-5"><input type="text" name="' + name + '_en[]" value="" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></div>'
                        + '<div class="col-span-5"><input type="text" name="' + name + '_bn[]" value="" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></div>'
                        + '<div class="col-span-1 flex items-center justify-end"><button type="button" data-cms-list-remove class="text-xs text-red-600 hover:text-red-800">×</button></div>';
                    wrap.appendChild(div);
                }
                if (t.closest('[data-cms-list-remove]')) {
                    const btn = t.closest('[data-cms-list-remove]');
                    const row = btn.closest('.cms-list-row');
                    if (row) row.remove();
                }
            });
        })();
    </script>
@endsection
