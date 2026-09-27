@extends('layouts.dashboard')

@section('title', ($design->exists ? __('Edit design') : __('New design')) . ' — ' . config('app.name'))

@php
    $theme = $design->exists
        ? $design->settings
        : app(App\Services\DocumentDesignService::class)->theme($design->document_type ?: 'certificate');
    $watermark = $design->exists ? (array) $design->watermark : (array) app(App\Services\DocumentDesignService::class)->watermark($design->document_type ?: 'certificate');
    $editing = $design->exists;
@endphp

@section('content')
    <x-page-header :title="$editing ? __('Edit design') : __('New design')" :description="__('Theme, watermark and custom CSS for one document type.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Document Designs'), 'url' => route('dashboard.document-designs.index')],
                ['label' => $editing ? __('Edit') : __('New')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-disc space-y-1 ps-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post"
          action="{{ $editing ? route('dashboard.document-designs.update', $design) : route('dashboard.document-designs.store') }}"
          id="design-form"
          class="grid gap-6 lg:grid-cols-2"
          data-preview-url="{{ route('dashboard.document-designs.preview') }}">
        @csrf
        @if ($editing) @method('put') @endif

        {{-- ------------------------------------------------------------ basics --}}
        <div class="space-y-6">
            <x-card :title="__('Basics')">
                <div class="space-y-4">
                    <div>
                        <label for="document_type" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Document type') }}</label>
                        <select id="document_type" name="document_type" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('document_type', $design->document_type) === $type)>
                                    {{ __('dashboard.document_type_'.$type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" required maxlength="120"
                               value="{{ old('name', $design->name) }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>

                    <div>
                        <label for="template" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Template') }}</label>
                        <select id="template" name="template" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach ($templates as $template)
                                <option value="{{ $template }}" @selected(old('template', $design->template ?: 'classic') === $template)>
                                    {{ __('dashboard.document_template_'.$template) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input name="is_default" type="checkbox" value="1" @checked(old('is_default', $design->is_default))>
                            {{ __('Use as the default for this type') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $design->is_active ?? true))>
                            {{ __('Active') }}
                        </label>
                    </div>
                </div>
            </x-card>

            {{-- ---------------------------------------------------------- colours --}}
            <x-card :title="__('Colours')">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'primary_color' => __('Primary'),
                        'secondary_color' => __('Secondary'),
                        'accent_color' => __('Accent'),
                        'text_color' => __('Text'),
                        'muted_color' => __('Muted'),
                        'background_color' => __('Background'),
                        'border_color' => __('Border'),
                    ] as $key => $label)
                        <div>
                            <label for="c-{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input id="c-{{ $key }}" name="settings[{{ $key }}]" type="color"
                                   value="{{ old('settings.'.$key, $theme[$key] ?? '#000000') }}"
                                   class="h-10 w-full rounded-lg border-slate-300">
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- ------------------------------------------------------------ sizing --}}
            <x-card :title="__('Layout')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="c-base_font_size" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Base font size (px)') }}</label>
                        <input id="c-base_font_size" name="settings[base_font_size]" type="number" min="8" max="32"
                               value="{{ old('settings.base_font_size', $theme['base_font_size'] ?? 14) }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="c-title_font_size" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Title font size (px)') }}</label>
                        <input id="c-title_font_size" name="settings[title_font_size]" type="number" min="10" max="72"
                               value="{{ old('settings.title_font_size', $theme['title_font_size'] ?? 20) }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="c-border_width" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Border width (px)') }}</label>
                        <input id="c-border_width" name="settings[border_width]" type="number" min="0" max="12"
                               value="{{ old('settings.border_width', $theme['border_width'] ?? 2) }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="c-border_radius" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Corner radius (px)') }}</label>
                        <input id="c-border_radius" name="settings[border_radius]" type="number" min="0" max="40"
                               value="{{ old('settings.border_radius', $theme['border_radius'] ?? 0) }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="c-border_style" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Border style') }}</label>
                        <select id="c-border_style" name="settings[border_style]" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach (['none', 'solid', 'double', 'dashed', 'dotted'] as $style)
                                <option value="{{ $style }}" @selected(old('settings.border_style', $theme['border_style'] ?? 'solid') === $style)>{{ $style }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="c-font_family" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Font') }}</label>
                        <select id="c-font_family" name="settings[font_family]" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach (['inherit', 'Georgia, serif', 'Helvetica, Arial, sans-serif', 'Arial, Helvetica, sans-serif', 'Times New Roman, serif', 'Verdana, sans-serif'] as $font)
                                <option value="{{ $font }}" @selected(old('settings.font_family', $theme['font_family'] ?? 'inherit') === $font)>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="c-accent_bar" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Accent bar') }}</label>
                        <select id="c-accent_bar" name="settings[accent_bar]" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach (['none', 'top', 'bottom', 'left', 'right'] as $bar)
                                <option value="{{ $bar }}" @selected(old('settings.accent_bar', $theme['accent_bar'] ?? 'none') === $bar)>{{ $bar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="c-page_size" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Paper size') }}</label>
                        <select id="c-page_size" name="settings[page_size]" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach (['a4', 'letter', 'legal', 'credit-card'] as $size)
                                <option value="{{ $size }}" @selected(old('settings.page_size', $theme['page_size'] ?? 'a4') === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="c-orientation" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Orientation') }}</label>
                        <select id="c-orientation" name="settings[orientation]" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach (['portrait', 'landscape'] as $orientation)
                                <option value="{{ $orientation }}" @selected(old('settings.orientation', $theme['orientation'] ?? 'portrait') === $orientation)>{{ $orientation }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="c-padding" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Padding (px)') }}</label>
                        <input id="c-padding" name="settings[padding]" type="number" min="0" max="160"
                               value="{{ old('settings.padding', $theme['padding'] ?? 32) }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                </div>
            </x-card>

            {{-- -------------------------------------------------------- watermark --}}
            <x-card :title="__('Watermark')">
                <div class="space-y-4">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input name="watermark[enabled]" type="checkbox" value="1" @checked(old('watermark.enabled', $watermark['enabled'] ?? false))>
                        {{ __('Print a watermark on this document type') }}
                    </label>

                    <div>
                        <label for="w-type" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Watermark type') }}</label>
                        <select id="w-type" name="watermark[type]" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach (['text', 'image', 'logo'] as $type)
                                <option value="{{ $type }}" @selected(old('watermark.type', $watermark['type'] ?? 'text') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="w-text" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Watermark text') }}</label>
                        <input id="w-text" name="watermark[text]" type="text" maxlength="120"
                               value="{{ old('watermark.text', $watermark['text'] ?? '') }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank to use the school name.') }}</p>
                    </div>

                    <div>
                        <label for="w-image_path" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Image path') }}</label>
                        <input id="w-image_path" name="watermark[image_path]" type="text" maxlength="255"
                               value="{{ old('watermark.image_path', $watermark['image_path'] ?? '') }}"
                               class="w-full rounded-lg border-slate-300 text-sm" placeholder="uploads/watermark.png">
                        <p class="mt-1 text-xs text-slate-500">{{ __('Relative path inside storage/app/public. Leave blank to use the school logo for the “logo” type.') }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="w-position" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Position') }}</label>
                            <select id="w-position" name="watermark[position]" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach (['center', 'diagonal', 'tile', 'top', 'bottom'] as $position)
                                    <option value="{{ $position }}" @selected(old('watermark.position', $watermark['position'] ?? 'diagonal') === $position)>{{ $position }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="w-rotation" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Rotation (degrees)') }}</label>
                            <input id="w-rotation" name="watermark[rotation]" type="number" min="-180" max="180"
                                   value="{{ old('watermark.rotation', $watermark['rotation'] ?? 45) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label for="w-font_size" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Font size (px)') }}</label>
                            <input id="w-font_size" name="watermark[font_size]" type="number" min="8" max="120"
                                   value="{{ old('watermark.font_size', $watermark['font_size'] ?? 48) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label for="w-opacity" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Opacity') }}</label>
                            <input id="w-opacity" name="watermark[opacity]" type="number" step="0.01" min="0.05" max="1"
                                   value="{{ old('watermark.opacity', $watermark['opacity'] ?? 0.18) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label for="w-color" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Colour') }}</label>
                            <input id="w-color" name="watermark[color]" type="color"
                                   value="{{ old('watermark.color', $watermark['color'] ?? '#0f172a') }}"
                                   class="h-10 w-full rounded-lg border-slate-300">
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- -------------------------------------------------------- custom css --}}
            <x-card :title="__('Custom CSS')">
                <label for="custom_css" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Custom CSS') }}</label>
                <textarea id="custom_css" name="custom_css" rows="6"
                          class="w-full rounded-lg border-slate-300 font-mono text-xs"
                          placeholder=".doc-root.doc-certificate .doc-title { letter-spacing: 3px; }">{{ old('custom_css', $design->custom_css ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('Appended after the generated design CSS. Scripts, remote URLs and unbalanced braces are stripped before saving.') }}
                </p>
            </x-card>

            <div class="flex gap-2">
                <x-button type="submit">{{ $editing ? __('Save changes') : __('Create design') }}</x-button>
                <x-button :href="route('dashboard.document-designs.index')" variant="secondary">{{ __('Cancel') }}</x-button>
            </div>
        </div>

        {{-- ------------------------------------------------------------- preview --}}
        <div class="lg:sticky lg:top-6 lg:self-start">
            <x-card :title="__('Preview')">
                <p class="mb-3 text-xs text-slate-500">{{ __('Re-renders as you edit. The PDF output uses the same rules.') }}</p>

                <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100 p-4">
                    <div class="mx-auto bg-white" id="preview-frame">
                        <div class="doc-root" id="preview-root">
                            <div class="doc-accent-bar"></div>
                            <div class="doc-watermark"><div class="doc-watermark-item" id="preview-watermark"></div></div>
                            <h1 class="doc-title" id="preview-title">{{ __('dashboard.document_type_certificate') }}</h1>
                            <p class="doc-name">{{ __('Preview Student Name') }}</p>
                            <p class="doc-number">{{ __('ADM-0000') }}</p>
                            <table class="doc-table">
                                <thead><tr><th>{{ __('Subject') }}</th><th>{{ __('Marks') }}</th></tr></thead>
                                <tbody>
                                    <tr><td>{{ __('Mathematics') }}</td><td class="doc-pass">82</td></tr>
                                    <tr><td>{{ __('English') }}</td><td>78</td></tr>
                                </tbody>
                            </table>
                            <div class="doc-panel doc-muted">{{ __('Signed by the principal') }}</div>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </form>

    @push('scripts')
        <script>
            // Live preview: the same normalisation the save path uses, applied to
            // the unsaved form, so the editor shows what will actually be stored.
            (function () {
                var form = document.getElementById('design-form');
                if (!form) return;

                var root = document.getElementById('preview-root');
                var style = document.getElementById('preview-style');
                var watermark = document.getElementById('preview-watermark');
                var title = document.getElementById('preview-title');
                var url = form.getAttribute('data-preview-url');
                var timer = null;

                function value(name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (!el) return '';
                    if (el.type === 'checkbox') return el.checked ? '1' : '';
                    return el.value;
                }

                function payload() {
                    var settings = {};
                    form.querySelectorAll('[name^="settings["]').forEach(function (el) {
                        if (el.type === 'checkbox') {
                            settings[el.name.replace(/^settings\[|\]$/g, '')] = el.checked;
                        } else {
                            settings[el.name.replace(/^settings\[|\]$/g, '')] = el.value;
                        }
                    });

                    var mark = {};
                    form.querySelectorAll('[name^="watermark["]').forEach(function (el) {
                        if (el.type === 'checkbox') {
                            mark[el.name.replace(/^watermark\[|\]$/g, '')] = el.checked;
                        } else {
                            mark[el.name.replace(/^watermark\[|\]$/g, '')] = el.value;
                        }
                    });
                    if (value('watermark[opacity]') !== '') mark.opacity = parseFloat(value('watermark[opacity]'));
                    if (value('watermark[font_size]') !== '') mark.font_size = parseInt(value('watermark[font_size]'), 10);
                    if (value('watermark[rotation]') !== '') mark.rotation = parseInt(value('watermark[rotation]'), 10);

                    return {
                        document_type: value('document_type') || 'certificate',
                        // `template` is a column of its own rather than part of
                        // the settings blob, so it has to be sent separately or the
                        // preview keeps rendering the previously stored template.
                        template: value('template') || 'classic',
                        settings: settings,
                        watermark: mark,
                        custom_css: value('custom_css')
                    };
                }

                function render(data) {
                    root.className = 'doc-root doc-' + data.type;
                    if (!style) {
                        style = document.createElement('style');
                        style.id = 'preview-style';
                        document.head.appendChild(style);
                    }
                    style.textContent = data.css;

                    var layer = root.querySelector('.doc-watermark');
                    if (layer) layer.style.display = data.watermark.enabled ? '' : 'none';

                    if (data.watermark.type === 'text') {
                        watermark.textContent = data.watermark.text || '';
                    } else {
                        watermark.innerHTML = '';
                        if (data.watermark.image_path) {
                            var img = document.createElement('img');
                            img.src = data.watermark.image_path;
                            watermark.appendChild(img);
                        }
                    }

                    title.textContent = data.type.replace(/_/g, ' ');
                }

                function refresh() {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value
                        },
                        body: JSON.stringify(payload())
                    })
                        .then(function (r) { return r.json(); })
                        .then(render)
                        .catch(function () { /* preview only — the form still saves */ });
                }

                form.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(refresh, 250);
                });
                form.addEventListener('change', refresh);

                refresh();
            })();
        </script>
    @endpush
@endsection
