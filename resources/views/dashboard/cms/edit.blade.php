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
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
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
                <a href="{{ route('home') }}{{ $page === 'home' ? '' : '/' . $page }}" target="_blank" rel="noopener" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                    {{ __('Preview public page') }} ↗
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                    {{ __('Save page') }}
                </button>
            </div>
        </div>
    </form>

    <script>
        (function () {
            // Hero fields visibility based on design
            var heroDesignSelect = document.getElementById('cms-hero_design');
            if (heroDesignSelect) {
                function toggleHeroFields() {
                    var design = heroDesignSelect.value;
                    var heroSection = heroDesignSelect.closest('form').querySelector('[data-hero-section]');
                    if (!heroSection) {
                        // Find the section containing "Hero content" heading
                        var sections = heroDesignSelect.closest('form').querySelectorAll('section');
                        sections.forEach(function(s) {
                            var h2 = s.querySelector('h2');
                            if (h2 && h2.textContent.indexOf('Hero') !== -1) {
                                heroSection = s;
                            }
                        });
                    }
                    if (!heroSection) return;
                    var isSlider = design === 'design-5' || design === 'design-6';
                    // Find image field (background_image)
                    var inputs = heroSection.querySelectorAll('input[type="url"], input[type="file"], button');
                    var imageField = null;
                    heroSection.querySelectorAll('div').forEach(function(div) {
                        var label = div.querySelector('label');
                        if (label && label.textContent.indexOf('Background image') !== -1) {
                            imageField = div;
                        }
                    });
                    if (imageField) {
                        imageField.style.display = isSlider ? 'none' : '';
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
