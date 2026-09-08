<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use App\Models\WebsiteMedia;
use App\Support\CmsPageRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CmsWebController extends Controller
{
    public function pages(): View
    {
        $slugs = array_keys(CmsPageRegistry::contentPages());
        $pages = WebsiteContent::query()
            ->whereIn('page', $slugs)
            ->orderBy('page')
            ->get();

        return view('dashboard.cms.pages', [
            'pages' => $pages,
            'registry' => CmsPageRegistry::all(),
        ]);
    }

    public function edit(Request $request, string $page): View
    {
        if (! CmsPageRegistry::exists($page)) {
            abort(404);
        }

        $content = WebsiteContent::query()->where('page', $page)->first();
        $def = CmsPageRegistry::get($page);

        if (! $content) {
            $title = $def['label'] ?? Str::title(str_replace('-', ' ', $page));
            $content = new WebsiteContent([
                'page' => $page,
                'title' => $title,
                'title_en' => $title,
                'title_bn' => $title,
                'content' => [],
                'content_en' => [],
                'content_bn' => [],
                'is_active' => true,
            ]);
        }

        $en = $content->englishContentTree();
        $bn = $content->bengaliContentTree();

        $values = [
            'en' => $en,
            'bn' => $bn,
        ];

        $visibilityKeys = config("cms_section_visibility.{$page}", []);

        return view('dashboard.cms.edit', [
            'content' => $content,
            'page' => $page,
            'def' => $def,
            'values' => $values,
            'visibilityKeys' => $visibilityKeys,
            'sectionVis' => optional(\App\Models\WebsiteSetting::first())->section_visibility ?? [],
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        if (! CmsPageRegistry::exists($page)) {
            abort(404);
        }

        $def = CmsPageRegistry::get($page);

        $validated = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'meta_description_en' => ['nullable', 'string', 'max:500'],
            'meta_description_bn' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fileRules = collect($this->flattenFileKeys($request->allFiles()))
            ->mapWithKeys(fn (string $field): array => [$field => ['file', 'image', 'max:10240']])
            ->all();

        if ($fileRules !== []) {
            $request->validate($fileRules);
        }

        [$contentEn, $contentBn] = $this->buildContentFromRequest($request, $def);

        $titleEn = $validated['title_en'];
        $titleBn = ($validated['title_bn'] ?? '') ?: $titleEn;
        $metaEn = $validated['meta_description_en'] ?? null;
        $metaBn = ($validated['meta_description_bn'] ?? '') ?: $metaEn;

        WebsiteContent::updateOrCreate(
            ['page' => $page],
            [
                'title' => $titleEn,
                'title_en' => $titleEn,
                'title_bn' => $titleBn,
                'content' => $contentEn,
                'content_en' => $contentEn,
                'content_bn' => $contentBn,
                'cms_input_mode' => WebsiteContent::INPUT_MODE_FORM,
                'meta_description' => $metaEn,
                'meta_description_en' => $metaEn,
                'meta_description_bn' => $metaBn,
                'meta_keywords' => $validated['meta_keywords'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]
        );

        // Persist per-section visibility for this CMS page. Only touch
        // visibility when the form submitted the `section_visibility` array.
        $visibilityKeys = config("cms_section_visibility.{$page}", []);
        if ($visibilityKeys !== [] && $request->has('section_visibility')) {
            $settings = \App\Models\WebsiteSetting::first() ?: \App\Models\WebsiteSetting::create([
                'school_name' => config('app.name'),
                'established_year' => (int) now()->format('Y'),
                'address' => '',
                'city' => '',
                'state' => '',
                'country' => '',
                'postal_code' => '',
                'phone' => '',
                'email' => '',
            ]);
            $existing = $settings->section_visibility ?? [];
            foreach ($visibilityKeys as $key => $label) {
                $existing[$key] = $request->boolean("section_visibility.{$key}");
            }
            $settings->section_visibility = $existing;
            $settings->save();
        }

        return redirect()
            ->route('dashboard.cms.edit', ['page' => $page])
            ->with('status', __('Page saved.'));
    }

    /**
     * Build the EN/BN content trees from the flat dot-notation form input.
     * Field names look like: "hero.headline_en", "sections.0.heading_en",
     * "highlights._en" (list), "testimonials.0.quote_en" (repeater), etc.
     *
     * @param  array<string, mixed>  $def
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function buildContentFromRequest(Request $request, array $def): array
    {
        $en = [];
        $bn = [];

        foreach ($def['sections'] ?? [] as $section) {
            $key = $section['key'];
            $type = $section['type'];
            // Form field names use underscores (PHP's parse_str flattens dots
            // to underscores in the input bag, so we use underscores in the
            // form and convert back to dots for the JSON tree).
            $formKey = $this->formKey($key);

            switch ($type) {
                case 'text':
                case 'textarea':
                    $valEn = trim((string) data_get($request->all(), $formKey.'_en', ''));
                    $valBn = trim((string) data_get($request->all(), $formKey.'_bn', ''));
                    if ($valEn !== '') {
                        Arr::set($en, $key, $valEn);
                    }
                    if ($valBn !== '' && $valBn !== $valEn) {
                        Arr::set($bn, $key, $valBn);
                    }
                    break;

                case 'image':
                    $shared = $section['fields'][0]['shared'] ?? false;
                    if ($shared) {
                        $val = $this->imageFieldValue($request, $formKey);
                        if ($val !== '') {
                            Arr::set($en, $key, $val);
                        }
                    } else {
                        $valEn = $this->imageFieldValue($request, $formKey.'_en');
                        $valBn = $this->imageFieldValue($request, $formKey.'_bn');
                        if ($valEn !== '') {
                            Arr::set($en, $key, $valEn);
                        }
                        if ($valBn !== '' && $valBn !== $valEn) {
                            Arr::set($bn, $key, $valBn);
                        }
                    }
                    break;

                case 'list':
                    $listEn = $this->filterList(data_get($request->all(), $formKey.'_en', []));
                    $listBn = $this->filterList(data_get($request->all(), $formKey.'_bn', []));
                    if ($listEn !== []) {
                        Arr::set($en, $key, array_values($listEn));
                    }
                    if ($listBn !== []) {
                        Arr::set($bn, $key, array_values($listBn));
                    }
                    break;

                case 'select':
                    $val = trim((string) data_get($request->all(), $formKey, ''));
                    if ($val !== '') {
                        Arr::set($en, $key, $val);
                    }
                    break;

                case 'slider':
                    [$sliderEn, $sliderBn] = $this->buildSlider($request, $formKey, $section);
                    if ($sliderEn !== []) {
                        Arr::set($en, $key, $sliderEn);
                    }
                    if ($sliderBn !== []) {
                        Arr::set($bn, $key, $sliderBn);
                    }
                    break;

                case 'repeater':
                case 'contact_cards':
                case 'repeater_sections':
                    [$secEn, $secBn] = $this->buildRepeater($request, $formKey, $section, $type);
                    if ($secEn !== []) {
                        Arr::set($en, $key, $secEn);
                    }
                    if ($secBn !== []) {
                        Arr::set($bn, $key, $secBn);
                    }
                    break;

                case 'hero':
                case 'kv':
                case 'group':
                    $subEn = [];
                    $subBn = [];
                    foreach ($section['fields'] ?? [] as $sub) {
                        $subFormKey = $formKey.'_'.$this->formKey($sub['key']);
                        $subType = $sub['type'] ?? 'text';
                        if ($subType === 'image') {
                            $shared = $sub['shared'] ?? false;
                            if ($shared) {
                                $v = $this->imageFieldValue($request, $subFormKey);
                                if ($v !== '') {
                                    Arr::set($subEn, $sub['key'], $v);
                                }
                            } else {
                                $vEn = $this->imageFieldValue($request, $subFormKey.'_en');
                                $vBn = $this->imageFieldValue($request, $subFormKey.'_bn');
                                if ($vEn !== '') {
                                    Arr::set($subEn, $sub['key'], $vEn);
                                }
                                if ($vBn !== '' && $vBn !== $vEn) {
                                    Arr::set($subBn, $sub['key'], $vBn);
                                }
                            }
                        } else {
                            $vEn = trim((string) data_get($request->all(), $subFormKey.'_en', ''));
                            $vBn = trim((string) data_get($request->all(), $subFormKey.'_bn', ''));
                            if ($vEn !== '') {
                                Arr::set($subEn, $sub['key'], $vEn);
                            }
                            if ($vBn !== '' && $vBn !== $vEn) {
                                Arr::set($subBn, $sub['key'], $vBn);
                            }
                        }
                    }
                    if ($subEn !== []) {
                        Arr::set($en, $key, $subEn);
                    }
                    if ($subBn !== []) {
                        Arr::set($bn, $key, $subBn);
                    }
                    break;
            }
        }

        return [$en, $bn];
    }

    /**
     * @return list<string>
     */
    protected function filterList(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }
        $out = [];
        foreach ($input as $v) {
            $v = trim((string) $v);
            if ($v !== '') {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * Build a repeater's EN/BN arrays from form input. Items are
     * addressed by integer index in the field name (sections[0][heading_en]).
     *
     * @param  array<string, mixed>  $section
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    protected function buildRepeater(Request $request, string $formKey, array $section, string $type): array
    {
        $all = $request->all();
        $pattern = '/^'.preg_quote($formKey, '/').'\[(\d+)\]\[([^\]]+)_(en|bn)\]$/';
        $indices = [];
        foreach ($this->flattenKeys($all) as $fieldName) {
            if (preg_match($pattern, $fieldName, $m)) {
                $idx = (int) $m[1];
                $subKey = $m[2];
                $locale = $m[3];
                $indices[$idx][$subKey][$locale] = true;
            }
        }
        if ($indices === []) {
            return [[], []];
        }
        ksort($indices);

        $subFields = $section['fields'] ?? null;
        $enItems = [];
        $bnItems = [];

        foreach ($indices as $i => $subKeys) {
            $enRow = [];
            $bnRow = [];

            if ($type === 'repeater_sections') {
                $hEn = trim((string) data_get($all, "{$formKey}.{$i}.heading_en", ''));
                $hBn = trim((string) data_get($all, "{$formKey}.{$i}.heading_bn", ''));
                $bEn = trim((string) data_get($all, "{$formKey}.{$i}.paragraphs_en", ''));
                $bBn = trim((string) data_get($all, "{$formKey}.{$i}.paragraphs_bn", ''));
                if ($hEn !== '' || $bEn !== '') {
                    $enRow = ['heading' => $hEn, 'paragraphs' => $this->splitParagraphs($bEn)];
                }
                if ($hBn !== '' || $bBn !== '') {
                    $bnRow = ['heading' => $hBn, 'paragraphs' => $this->splitParagraphs($bBn)];
                }
            } elseif ($type === 'contact_cards') {
                $lEn = trim((string) data_get($all, "{$formKey}.{$i}.label_en", ''));
                $lBn = trim((string) data_get($all, "{$formKey}.{$i}.label_bn", ''));
                $pEn = trim((string) data_get($all, "{$formKey}.{$i}.phone_en", ''));
                $pBn = trim((string) data_get($all, "{$formKey}.{$i}.phone_bn", ''));
                if ($lEn !== '' || $pEn !== '') {
                    $enRow = ['label' => $lEn, 'phone' => $pEn];
                }
                if ($lBn !== '' || $pBn !== '') {
                    $bnRow = ['label' => $lBn, 'phone' => $pBn];
                }
            } else {
                foreach ($subFields as $sub) {
                    $sk = $sub['key'];
                    $vEn = trim((string) data_get($all, "{$formKey}.{$i}.{$sk}_en", ''));
                    $vBn = trim((string) data_get($all, "{$formKey}.{$i}.{$sk}_bn", ''));
                    if ($vEn !== '') {
                        $enRow[$sk] = $vEn;
                    }
                    if ($vBn !== '' && $vBn !== $vEn) {
                        $bnRow[$sk] = $vBn;
                    }
                }
            }

            if ($enRow !== []) {
                $enItems[] = $enRow;
            }
            if ($bnRow !== []) {
                $bnItems[] = $bnRow;
            }
        }

        return [$enItems, $bnItems];
    }

    /**
     * Convert a JSON-tree key (e.g. "hero.headline") into a form-field-safe
     * key (e.g. "hero_headline"). PHP's parse_str flattens dots to
     * underscores in submitted field names, so the form and the JSON tree
     * differ in that respect.
     */
    protected function formKey(string $key): string
    {
        return str_replace('.', '_', $key);
    }

    /**
     * Build a slider's EN/BN arrays. Each slide has a shared image subfield
     * (file upload or URL) plus text subfields addressed like
     * "slider[0][title_en]". Form fields use bracket notation, so files are
     * read from the file bag and text from the dot-notation data tree.
     *
     * @param  array<string, mixed>  $section
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    protected function buildSlider(Request $request, string $formKey, array $section): array
    {
        $all = $request->all();
        $pattern = '/^'.preg_quote($formKey, '/').'\[(\d+)\]\[([^\]]+)\]$/';
        $indices = [];
        foreach ($this->flattenKeys($all) as $fieldName) {
            if (preg_match($pattern, $fieldName, $m)) {
                $indices[(int) $m[1]][$m[2]] = true;
            }
        }
        foreach (array_keys($request->allFiles()) as $fieldName) {
            if (preg_match($pattern, (string) $fieldName, $m)) {
                $indices[(int) $m[1]][$m[2]] = true;
            }
        }
        if ($indices === []) {
            return [[], []];
        }
        ksort($indices);

        $enItems = [];
        $bnItems = [];

        foreach ($indices as $i => $subKeys) {
            $enRow = [];
            $bnRow = [];

            foreach ($section['fields'] ?? [] as $sub) {
                $sk = $sub['key'];
                $isImage = ($sub['type'] ?? 'text') === 'image';
                $dotKey = "{$formKey}.{$i}.{$sk}";

                if ($isImage) {
                    $v = '';
                    if ($request->hasFile($dotKey)) {
                        $file = $request->file($dotKey);
                        $path = $file->store('media', 'public');

                        WebsiteMedia::create([
                            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                            'category' => 'CMS',
                            'file_path' => $path,
                            'mime_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                        ]);

                        $v = '/storage/'.ltrim($path, '/');
                    } else {
                        $v = trim((string) data_get($all, $dotKey, ''));
                    }
                    if ($v !== '') {
                        $enRow[$sk] = $v;
                    }
                } else {
                    $vEn = trim((string) data_get($all, "{$formKey}.{$i}.{$sk}_en", ''));
                    $vBn = trim((string) data_get($all, "{$formKey}.{$i}.{$sk}_bn", ''));
                    if ($vEn !== '') {
                        $enRow[$sk] = $vEn;
                    }
                    if ($vBn !== '' && $vBn !== $vEn) {
                        $bnRow[$sk] = $vBn;
                    }
                }
            }

            if ($enRow !== []) {
                $enItems[] = $enRow;
            }
            if ($bnRow !== []) {
                $bnItems[] = $bnRow;
            }
        }

        return [$enItems, $bnItems];
    }

    /**
     * Resolve an image field value. If a file was uploaded for the given
     * form key it is stored in the media library and its public URL is
     * returned; otherwise the submitted text (URL) value is returned.
     */
    protected function imageFieldValue(Request $request, string $formKey): string
    {
        if ($request->hasFile($formKey)) {
            $file = $request->file($formKey);
            $path = $file->store('media', 'public');

            WebsiteMedia::create([
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'category' => 'CMS',
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return '/storage/'.ltrim($path, '/');
        }

        return trim((string) $request->input($formKey, ''));
    }

    /**
     * Flatten the (possibly nested) upload file bag into dot-notation keys.
     * A form field named "slider[0][image]" arrives as
     * ['slider' => [0 => ['image' => UploadedFile]]] and becomes
     * "slider.0.image" so the validator and file() lookups can find it.
     *
     * @param  array<mixed>  $files
     * @return list<string>
     */
    protected function flattenFileKeys(array $files, string $prefix = ''): array
    {
        $out = [];
        foreach ($files as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $out[] = $name;
            } elseif (is_array($value)) {
                $out = array_merge($out, $this->flattenFileKeys($value, $name));
            }
        }

        return $out;
    }

    /**
     * Yield every leaf key in a nested array, written in PHP's parse_str
     * form (e.g. "sections[0][heading_en]"). Used to scan submitted form
     * data for repeater fields regardless of nesting depth.
     *
     * @param  iterable<string, mixed>  $data
     * @return iterable<string>
     */
    protected function flattenKeys(iterable $data, string $prefix = ''): iterable
    {
        foreach ($data as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';
            if (is_array($value)) {
                yield from $this->flattenKeys($value, $name);
            } else {
                yield $name;
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function splitParagraphs(string $text): array
    {
        if ($text === '') {
            return [];
        }
        $parts = preg_split("/\r\n|\r|\n/", $text) ?: [];
        $parts = array_map('trim', $parts);

        return array_values(array_filter($parts, fn ($p) => $p !== ''));
    }
}
