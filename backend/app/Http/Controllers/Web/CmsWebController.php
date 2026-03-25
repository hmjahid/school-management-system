<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CmsWebController extends Controller
{
    public function pages(): View
    {
        $pages = WebsiteContent::query()->orderBy('page')->paginate(20);

        return view('dashboard.cms.pages', compact('pages'));
    }

    public function edit(string $page): View
    {
        $content = WebsiteContent::query()->where('page', $page)->first();

        if (! $content) {
            $title = Str::title(str_replace('-', ' ', $page));
            $content = new WebsiteContent([
                'page' => $page,
                'title' => $title,
                'title_en' => $title,
                'title_bn' => $title,
                'content' => [],
                'content_en' => [],
                'content_bn' => [],
                'cms_input_mode' => WebsiteContent::INPUT_MODE_JSON,
                'is_active' => true,
            ]);
        }

        $en = $content->englishContentTree();
        $bn = $content->bengaliContentTree();

        $jsonEn = json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
        $jsonBn = json_encode($bn !== [] ? $bn : $en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';

        $allowFormMode = $page !== 'site-ui';
        $formSections = $this->pairedSectionsForForm($en, $bn);
        if (old('form_sections')) {
            $formSections = old('form_sections', $formSections);
        }

        $formIntroEn = old('form_intro_en', $en['intro'] ?? '');
        $formIntroBn = old('form_intro_bn', $bn['intro'] ?? ($en['intro'] ?? ''));

        $heroEn = $en['hero'] ?? [];
        $heroBn = $bn['hero'] ?? [];
        $formHeroHeadlineEn = old('form_hero_headline_en', $heroEn['headline'] ?? '');
        $formHeroHeadlineBn = old('form_hero_headline_bn', $heroBn['headline'] ?? ($heroEn['headline'] ?? ''));
        $formHeroSubtitleEn = old('form_hero_subtitle_en', $heroEn['subtitle'] ?? $heroEn['motto'] ?? '');
        $formHeroSubtitleBn = old('form_hero_subtitle_bn', $heroBn['subtitle'] ?? $heroBn['motto'] ?? ($heroEn['subtitle'] ?? $heroEn['motto'] ?? ''));
        $formHeroBackground = old('form_hero_background', $heroEn['background_image'] ?? $heroBn['background_image'] ?? '');

        return view('dashboard.cms.edit', [
            'content' => $content,
            'page' => $page,
            'contentJsonEn' => old('content_json_en', $jsonEn),
            'contentJsonBn' => old('content_json_bn', $jsonBn),
            'allowFormMode' => $allowFormMode,
            'formSections' => $formSections,
            'formIntroEn' => $formIntroEn,
            'formIntroBn' => $formIntroBn,
            'formHeroHeadlineEn' => $formHeroHeadlineEn,
            'formHeroHeadlineBn' => $formHeroHeadlineBn,
            'formHeroSubtitleEn' => $formHeroSubtitleEn,
            'formHeroSubtitleBn' => $formHeroSubtitleBn,
            'formHeroBackground' => $formHeroBackground,
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        if ($page === 'site-ui') {
            $request->merge(['cms_input_mode' => WebsiteContent::INPUT_MODE_JSON]);
        }

        $mode = $request->input('cms_input_mode', WebsiteContent::INPUT_MODE_JSON);
        if (! in_array($mode, [WebsiteContent::INPUT_MODE_JSON, WebsiteContent::INPUT_MODE_FORM], true)) {
            $mode = WebsiteContent::INPUT_MODE_JSON;
        }
        if ($page === 'site-ui') {
            $mode = WebsiteContent::INPUT_MODE_JSON;
        }

        $validated = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'meta_description_en' => ['nullable', 'string', 'max:500'],
            'meta_description_bn' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'cms_input_mode' => ['required', 'in:json,form'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($mode === WebsiteContent::INPUT_MODE_JSON) {
            $jsonRules = $request->validate([
                'content_json_en' => ['required', 'string'],
                'content_json_bn' => ['nullable', 'string'],
            ]);

            $contentEn = json_decode($jsonRules['content_json_en'], true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($contentEn)) {
                return back()->withErrors(['content_json_en' => __('English content must be valid JSON (object or array).')])->withInput();
            }

            $rawBn = trim($jsonRules['content_json_bn'] ?? '');
            if ($rawBn === '') {
                $contentBn = [];
            } else {
                $contentBn = json_decode($rawBn, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($contentBn)) {
                    return back()->withErrors(['content_json_bn' => __('Bengali content must be valid JSON (object or array).')])->withInput();
                }
            }
        } else {
            [$patchEn, $patchBn] = $this->buildFormContents($request, $page);
            $old = WebsiteContent::query()->where('page', $page)->first();
            $oldEn = $old?->englishContentTree() ?? [];
            $oldBn = $old?->bengaliContentTree() ?? [];
            $contentEn = array_replace_recursive($oldEn, $patchEn);
            $contentBn = array_replace_recursive($oldBn, $patchBn);
        }

        $titleEn = $validated['title_en'];
        $titleBn = $validated['title_bn'] ?: $titleEn;
        $metaEn = $validated['meta_description_en'] ?? null;
        $metaBn = $validated['meta_description_bn'] ?? null;
        if ($metaBn === null || $metaBn === '') {
            $metaBn = $metaEn;
        }

        WebsiteContent::updateOrCreate(
            ['page' => $page],
            [
                'title' => $titleEn,
                'title_en' => $titleEn,
                'title_bn' => $titleBn,
                'content' => $contentEn,
                'content_en' => $contentEn,
                'content_bn' => $contentBn,
                'cms_input_mode' => $mode,
                'meta_description' => $metaEn,
                'meta_description_en' => $metaEn,
                'meta_description_bn' => $metaBn,
                'meta_keywords' => $validated['meta_keywords'] ?? null,
                'is_active' => $request->has('is_active'),
            ]
        );

        return redirect()
            ->route('dashboard.cms.edit', ['page' => $page])
            ->with('status', __('Page saved.'));
    }

    /**
     * @param  array<string, mixed>  $en
     * @param  array<string, mixed>  $bn
     * @return list<array{heading_en: string, heading_bn: string, body_en: string, body_bn: string}>
     */
    protected function pairedSectionsForForm(array $en, array $bn): array
    {
        $se = $en['sections'] ?? [];
        $sb = $bn['sections'] ?? [];
        if (! is_array($se)) {
            $se = [];
        }
        if (! is_array($sb)) {
            $sb = [];
        }

        $n = max(count($se), count($sb));
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $e = is_array($se[$i] ?? null) ? $se[$i] : [];
            $b = is_array($sb[$i] ?? null) ? $sb[$i] : [];
            $parEn = $e['paragraphs'] ?? [];
            $parBn = $b['paragraphs'] ?? [];
            $rows[] = [
                'heading_en' => (string) ($e['heading'] ?? ''),
                'heading_bn' => (string) ($b['heading'] ?? ''),
                'body_en' => is_array($parEn) ? implode("\n\n", $parEn) : '',
                'body_bn' => is_array($parBn) ? implode("\n\n", $parBn) : '',
            ];
        }

        if ($n === 0) {
            $rows[] = [
                'heading_en' => '',
                'heading_bn' => '',
                'body_en' => '',
                'body_bn' => '',
            ];
        }

        return $rows;
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function buildFormContents(Request $request, string $page): array
    {
        $introEn = trim((string) $request->input('form_intro_en', ''));
        $introBn = trim((string) $request->input('form_intro_bn', ''));

        $secEn = [];
        $secBn = [];
        foreach ($request->input('form_sections', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $hEn = trim((string) ($row['heading_en'] ?? ''));
            $hBn = trim((string) ($row['heading_bn'] ?? ''));
            $bEn = trim((string) ($row['body_en'] ?? ''));
            $bBn = trim((string) ($row['body_bn'] ?? ''));

            if ($hEn !== '' || $bEn !== '') {
                $paras = $bEn === '' ? [] : array_values(array_filter(preg_split("/\r\n|\r|\n/", $bEn), fn ($l) => trim((string) $l) !== ''));
                $secEn[] = [
                    'heading' => $hEn,
                    'paragraphs' => $paras,
                ];
            }
            if ($hBn !== '' || $bBn !== '') {
                $paras = $bBn === '' ? [] : array_values(array_filter(preg_split("/\r\n|\r|\n/", $bBn), fn ($l) => trim((string) $l) !== ''));
                $secBn[] = [
                    'heading' => $hBn,
                    'paragraphs' => $paras,
                ];
            }
        }

        $contentEn = [];
        $contentBn = [];
        if ($introEn !== '') {
            $contentEn['intro'] = $introEn;
        }
        if ($introBn !== '') {
            $contentBn['intro'] = $introBn;
        }
        if ($secEn !== []) {
            $contentEn['sections'] = array_values($secEn);
        }
        if ($secBn !== []) {
            $contentBn['sections'] = array_values($secBn);
        }

        if ($page === 'home') {
            $bg = trim((string) $request->input('form_hero_background', ''));
            $he = trim((string) $request->input('form_hero_headline_en', ''));
            $hb = trim((string) $request->input('form_hero_headline_bn', ''));
            $se = trim((string) $request->input('form_hero_subtitle_en', ''));
            $sb = trim((string) $request->input('form_hero_subtitle_bn', ''));

            if ($he !== '' || $se !== '' || $bg !== '') {
                $contentEn['hero'] = array_filter([
                    'headline' => $he,
                    'subtitle' => $se,
                    'motto' => $se,
                    'background_image' => $bg !== '' ? $bg : null,
                ], fn ($v) => $v !== null && $v !== '');
            }
            if ($hb !== '' || $sb !== '' || $bg !== '') {
                $contentBn['hero'] = array_filter([
                    'headline' => $hb,
                    'subtitle' => $sb,
                    'motto' => $sb,
                    'background_image' => $bg !== '' ? $bg : null,
                ], fn ($v) => $v !== null && $v !== '');
            }
        }

        return [$contentEn, $contentBn];
    }
}
