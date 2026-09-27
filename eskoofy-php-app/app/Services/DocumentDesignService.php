<?php

namespace App\Services;

use App\Models\DocumentDesign;
use App\Core\Schema;
use App\Core\Support\Str;

/**
 * Resolves the active design + watermark for every exportable document type
 * (certificate, testimonial, marksheet, admit card, student ID card).
 *
 * Layering, lowest priority first:
 *   1. `config/eskoolfy.php` → `documents.defaults` / `documents.watermark`
 *      (the shipped bd-profile look, and the bd/int profile data).
 *   2. the install's active `document_designs` row for that type
 *      (`settings` + `watermark` + sanitised `custom_css`).
 *
 * Everything this service returns is already normalised and clamped, so the
 * views/printers only have to echo it. That is what keeps the four products'
 * implementations auditable against each other: identical input config ⇒
 * identical normalised output.
 */
class DocumentDesignService
{
    public const TYPES = ['certificate', 'testimonial', 'marksheet', 'admit_card', 'id_card'];

    public const TEMPLATES = ['classic', 'modern', 'minimal', 'bordered'];

    private const CACHE_KEY = 'document_designs.active';

    private const CUSTOM_CSS_MAX_BYTES = 16384;

    /** @var array<string, array<string, mixed>> */
    private array $memo = [];

    // ------------------------------------------------------------------- types

    /** @return array<int, string> */
    public static function types(): array
    {
        $types = (array) config('eskoolfy.documents.types', self::TYPES);

        return array_values(array_intersect($types, self::TYPES));
    }

    public static function isType(?string $type): bool
    {
        return $type !== null && in_array($type, self::TYPES, true);
    }

    public static function templates(): array
    {
        return (array) config('eskoolfy.documents.templates', self::TEMPLATES);
    }

    public static function isTemplate(?string $template): bool
    {
        return $template !== null && in_array($template, self::templates(), true);
    }

    public static function label(string $type): string
    {
        return (string) __('dashboard.document_type_'.$type);
    }

    public static function templateLabel(string $template): string
    {
        return (string) __('dashboard.document_template_'.$template);
    }

    // ------------------------------------------------------------- resolution

    /**
     * The active design row for a document type, or null when the install has
     * not customised it (so the config defaults apply).
     */
    public function activeDesign(string $type): ?DocumentDesign
    {
        if (! self::isType($type) || ! $this->tableExists()) {
            return null;
        }

        if (isset($this->memo['active'][$type])) {
            return $this->memo['active'][$type];
        }

        $design = DocumentDesign::query()
            ->where('document_type', $type)
            ->where('is_default', true)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first();

        return $this->memo['active'][$type] = ($design ?: null);

    }

    /**
     * Normalised theme for a document type: template + colours + metrics.
     *
     * @return array<string, mixed>
     */
    public function theme(string $type): array
    {
        if (isset($this->memo['theme'][$type])) {
            return $this->memo['theme'][$type];
        }

        $defaults = (array) config('eskoolfy.documents.defaults.'.$type, []);
        $design = $this->activeDesign($type);
        $settings = (array) ($design?->settings ?: []);

        $theme = $this->sanitizeTheme(array_merge($defaults, $settings));
        // The template lives in its own column so it can be filtered/sorted in
        // the admin list; it may also be repeated inside `settings` by an older
        // payload, and the column then wins.
        $template = $design?->template ?? $settings['template'] ?? null;
        $theme['template'] = self::isTemplate($template)
            ? (string) $template
            : (self::isTemplate($defaults['template'] ?? null) ? (string) $defaults['template'] : 'classic');
        $theme['document_type'] = $type;
        $theme['name'] = (string) ($design->name ?? self::label($type));
        $theme['has_custom_design'] = $design !== null;

        return $this->memo['theme'][$type] = $theme;
    }

    /**
     * Normalised watermark for a document type.
     *
     * @return array{enabled: bool, type: string, text: string|null, image_path: string|null, opacity: float, rotation: int, position: string, font_size: int, color: string, font_family: string}
     */
    public function watermark(string $type): array
    {
        if (isset($this->memo['watermark'][$type])) {
            return $this->memo['watermark'][$type];
        }

        $config = (array) config('eskoolfy.documents.watermark', []);
        $perDocument = (array) ($config['documents'] ?? []);

        $design = $this->activeDesign($type);
        $override = (array) ($design?->watermark ?: []);

        $merged = array_merge($config, $override);

        // Two independent switches, not one chain: `enabled` (config, or the
        // design row's explicit override) is the master switch, and
        // `documents.*` then selects which types it applies to. Merging them
        // into a single `??` chain would let a per-type entry re-enable a
        // watermark the install had globally switched off.
        $global = array_key_exists('enabled', $override)
            ? (bool) $override['enabled']
            : (bool) ($config['enabled'] ?? false);
        $selected = array_key_exists($type, $perDocument) ? (bool) $perDocument[$type] : true;

        $merged['enabled'] = $global && $selected;

        return $this->memo['watermark'][$type] = $this->sanitizeWatermark($merged, $type);
    }

    public function hasWatermark(string $type): bool
    {
        return $this->watermark($type)['enabled'];
    }

    /**
     * The full render context for a document type: theme, watermark and the
     * sanitised custom CSS. This is what views and the preview endpoint use.
     *
     * @return array{type: string, theme: array<string, mixed>, watermark: array<string, mixed>, custom_css: string, css: string}
     */
    public function context(string $type): array
    {
        $theme = $this->theme($type);
        $watermark = $this->watermark($type);

        return [
            'type' => $type,
            'theme' => $theme,
            'watermark' => $watermark,
            'custom_css' => $this->customCss($type),
            'css' => $this->css($type),
        ];
    }

    /**
     * Sanitised custom CSS for a document type (empty when none is stored).
     */
    public function customCss(string $type): string
    {
        $css = (string) ($this->activeDesign($type)?->custom_css ?? '');
        $css = $this->sanitizeCss($css);

        return strlen($css) > self::CUSTOM_CSS_MAX_BYTES
            ? substr($css, 0, self::CUSTOM_CSS_MAX_BYTES)
            : $css;
    }

    /**
     * The CSS block injected into every print/PDF template: CSS custom
     * properties for the browser, the template's layout rules, the watermark
     * layer rules, and the custom CSS last so it can override anything.
     *
     * Every rule below uses *literal* values rather than `var()`/`calc()`/
     * `color-mix()`: dompdf (used for the certificate, testimonial, admit-card,
     * ID-card and marksheet PDFs) supports none of those, so a var()-based
     * design would silently lose all its colours in the PDF while looking
     * correct on screen. The `:root` variable block is kept as well — it is what
     * makes a design inspectable/overridable in the browser preview.
     */
    public function css(string $type): string
    {
        return $this->cssFor($this->theme($type), $this->watermark($type), $this->customCss($type));
    }

    /**
     * Build the CSS block from an arbitrary theme/watermark pair rather than from
     * the stored design. This is what the admin live preview uses: it can pass
     * the *unsaved* form values and get byte-identical output to what the next
     * save will produce, because the same private builders are used either way.
     *
     * @param  array<string, mixed>  $theme
     * @param  array<string, mixed>  $watermark
     */
    public function cssFor(array $theme, array $watermark, string $customCss = ''): string
    {
        $type = (string) ($theme['document_type'] ?? 'certificate');
        // sanitizeTheme() does not carry `template` (it is a column on the design
        // row, not a theme setting), so it has to be held across the call.
        $template = $theme['template'] ?? 'classic';

        $theme = $this->sanitizeTheme($theme);
        $theme['document_type'] = self::isType($type) ? $type : 'certificate';
        $theme['template'] = self::isTemplate($template) ? (string) $template : 'classic';

        $watermark = $this->sanitizeWatermark($watermark, $theme['document_type']);

        $root = $this->rootSelector($theme);

        $declarations = '';
        foreach ($this->cssVars($theme) as $name => $value) {
            $declarations .= $name.':'.$value.';';
        }

        $css = $root.'{'.$declarations.'}';
        $css .= $this->templateCss($theme);
        $css .= $this->accentBarCss($theme);
        $css .= $this->pageCss($theme);
        $css .= $this->watermarkCss($watermark, $root);

        $custom = $this->sanitizeCss($customCss);
        if ($custom !== '') {
            $css .= "\n".$custom;
        }

        return $css;
    }

    /**
     * The design tokens as CSS custom properties, keyed by custom-property name.
     *
     * @param  array<string, mixed>  $theme
     * @return array<string, string>
     */
    private function cssVars(array $theme): array
    {
        return [
            '--doc-primary' => $theme['primary_color'],
            '--doc-secondary' => $theme['secondary_color'],
            '--doc-accent' => $theme['accent_color'],
            '--doc-text' => $theme['text_color'],
            '--doc-muted' => $theme['muted_color'],
            '--doc-bg' => $theme['background_color'],
            '--doc-border' => $theme['border_color'],
            '--doc-font' => $theme['font_family'],
            '--doc-base-size' => $theme['base_font_size'].'px',
            '--doc-title-size' => $theme['title_font_size'].'px',
            '--doc-border-style' => $theme['border_style'],
            '--doc-border-width' => $theme['border_width'].'px',
            '--doc-radius' => $theme['border_radius'].'px',
            '--doc-padding' => $theme['padding'].'px',
            // Pre-computed tints: the view stylesheets can use them, and they
            // stay correct without needing color-mix() support.
            '--doc-tint' => $this->tint($theme['accent_color'], 0.08),
            '--doc-tint-strong' => $this->tint($theme['primary_color'], 0.10),
        ];
    }

    // ------------------------------------------------------------ input rules

    /**
     * Clamp/sanitise a submitted settings payload.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function sanitizeTheme(array $input): array
    {
        // Neutral hard fallbacks, deliberately NOT the certificate defaults:
        // every type must fall back to grey/black so a type that omits a key in
        // config never inherits another type's brand colour.
        $input = array_merge([
            'primary_color' => '#1e40af',
            'secondary_color' => '#1e3a8a',
            'accent_color' => '#2563eb',
            'text_color' => '#1f2937',
            'muted_color' => '#64748b',
            'background_color' => '#ffffff',
            'border_color' => '#1e40af',
            'font_family' => 'inherit',
            'base_font_size' => 14,
            'title_font_size' => 20,
            'border_style' => 'solid',
            'border_width' => 2,
            'border_radius' => 0,
            'padding' => 32,
            'page_size' => 'a4',
            'orientation' => 'portrait',
            'accent_bar' => 'none',
        ], $input);

        $fonts = [
            'Georgia, serif', 'Helvetica, Arial, sans-serif', 'Arial, Helvetica, sans-serif',
            'Times New Roman, serif', 'Verdana, sans-serif', 'inherit',
        ];

        return [
            'primary_color' => $this->color($input['primary_color'], '#1e40af'),
            'secondary_color' => $this->color($input['secondary_color'], '#1e3a8a'),
            'accent_color' => $this->color($input['accent_color'], '#2563eb'),
            'text_color' => $this->color($input['text_color'], '#1f2937'),
            'muted_color' => $this->color($input['muted_color'], '#64748b'),
            'background_color' => $this->color($input['background_color'], '#ffffff'),
            'border_color' => $this->color($input['border_color'], '#1e40af'),
            'font_family' => in_array($input['font_family'] ?? '', $fonts, true) ? (string) $input['font_family'] : 'inherit',
            'base_font_size' => $this->clampInt($input['base_font_size'], 8, 32, 14),
            'title_font_size' => $this->clampInt($input['title_font_size'], 10, 72, 20),
            'border_style' => in_array($input['border_style'] ?? '', ['none', 'solid', 'double', 'dashed', 'dotted'], true)
                ? (string) $input['border_style'] : 'solid',
            'border_width' => $this->clampInt($input['border_width'], 0, 12, 2),
            'border_radius' => $this->clampInt($input['border_radius'], 0, 40, 0),
            'padding' => $this->clampInt($input['padding'], 0, 160, 32),
            'page_size' => in_array($input['page_size'] ?? '', ['a4', 'letter', 'legal', 'credit-card'], true)
                ? (string) $input['page_size'] : 'a4',
            'orientation' => in_array($input['orientation'] ?? '', ['portrait', 'landscape'], true)
                ? (string) $input['orientation'] : 'portrait',
            'accent_bar' => in_array($input['accent_bar'] ?? '', ['none', 'top', 'bottom', 'left', 'right'], true)
                ? (string) $input['accent_bar'] : 'none',
            'show_header' => $this->flag($input['show_header'] ?? true, true),
            'show_footer' => $this->flag($input['show_footer'] ?? true, true),
            'show_logo' => $this->flag($input['show_logo'] ?? true, true),
            'show_number' => $this->flag($input['show_number'] ?? true, true),
            'show_issue_date' => $this->flag($input['show_issue_date'] ?? true, true),
            'show_signature' => $this->flag($input['show_signature'] ?? true, true),
            'show_notes' => $this->flag($input['show_notes'] ?? true, true),
            'logo_position' => in_array($input['logo_position'] ?? '', ['top-left', 'top-center'], true)
                ? (string) $input['logo_position'] : 'top-center',
            'header_text' => $this->line($input['header_text'] ?? null, 255),
            'footer_text' => $this->line($input['footer_text'] ?? null, 255),
            'signature_label' => $this->line($input['signature_label'] ?? null, 120),
        ];
    }

    /**
     * Clamp/sanitise a submitted watermark payload.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function sanitizeWatermark(array $input, ?string $type = null): array
    {
        $text = $this->line($input['text'] ?? null, 120);
        $school = config('app.name');
        $type_value = in_array($input['type'] ?? '', ['text', 'image', 'logo'], true) ? (string) $input['type'] : 'text';

        $documents = [];
        foreach (self::TYPES as $docType) {
            $documents[$docType] = (bool) ($input['documents'][$docType] ?? true);
        }
        if ($type !== null && array_key_exists('documents', $input)) {
            $documents[$type] = (bool) $input['documents'][$type];
        }

        $image = $this->uploadPath($input['image_path'] ?? null);

        // A `logo` watermark with no explicit upload falls back to the site's own
        // logo, so the view partial only ever has to render `image_path`.
        if ($type_value === 'logo' && $image === null) {
            $image = $this->siteLogoPath();
        }

        return [
            'enabled' => (bool) ($input['enabled'] ?? false),
            'type' => $type_value,
            'text' => $text ?? ($type_value === 'text' ? (string) $school : null),
            'image_path' => $image,
            'opacity' => $this->clampFloat($input['opacity'] ?? 0.18, 0.05, 1.0, 0.18),
            'rotation' => $this->clampInt($input['rotation'] ?? 45, -180, 180, 45),
            'position' => in_array($input['position'] ?? '', ['center', 'diagonal', 'tile', 'top', 'bottom'], true)
                ? (string) $input['position'] : 'diagonal',
            'font_size' => $this->clampInt($input['font_size'] ?? 48, 8, 120, 48),
            'color' => $this->color($input['color'] ?? '#0f172a', '#0f172a'),
            'font_family' => $this->line($input['font_family'] ?? 'inherit', 120) ?? 'inherit',
            'documents' => $documents,
        ];
    }

    /**
     * Strip anything executable from custom CSS, keeping valid declarations
     * (`;` and `{}` must survive) so an install can still restyle a document.
     *
     * `<` and `>` are removed, which is what makes the result safe to inject
     * into a `<style>` block: without them `</style>` cannot be reconstructed.
     * Remote `url()` targets are dropped for the same reason the PDF renderer
     * cannot fetch them.
     */
    public function sanitizeCss(string $css): string
    {
        $css = strip_tags($css);
        $css = str_ireplace(['javascript:', 'vbscript:', 'expression(', '@import', '-moz-binding', 'behavior:', '@charset'], '', $css) ?? '';
        // url() may only point at a relative path (no protocol, no traversal).
        $css = preg_replace_callback('#url\(([^)]*)\)#i', function (array $m): string {
            $value = trim($m[1], " \t\"'");
            if ($value === '' || preg_match('#^(https?:|data:|//|\.\./)#i', $value)) {
                return 'none';
            }

            return 'url('.$value.')';
        }, $css) ?? '';
        // Angle brackets only — never semicolons or braces.
        $css = str_replace(['<', '>'], '', $css) ?? $css;
        // An unterminated comment would swallow the rest of the stylesheet.
        $css = str_replace(['/*', '*/'], '', $css) ?? $css;

        if (preg_match('/[{}]/', $css) && substr_count($css, '{') !== substr_count($css, '}')) {
            return '';
        }

        return trim($css);
    }

    public static function flushCache(): void
    {
        // The raw-PHP port keeps active designs in a per-process memo; flushing
        // it is a no-op because each request builds a fresh service instance.
    }

    // ------------------------------------------------------------------- css

    /** @param array<string, mixed> $theme */
    private function rootSelector(array $theme): string
    {
        return '.doc-root.doc-'.preg_replace('/[^a-z0-9_-]/i', '', (string) $theme['document_type']);
    }

    /** @param array<string, mixed> $theme */
    private function templateCss(array $theme): string
    {
        $root = $this->rootSelector($theme);

        $base = $root.'{'
            .'font-family:'.$theme['font_family'].';font-size:'.$theme['base_font_size'].'px;color:'.$theme['text_color'].';'
            .'background:'.$theme['background_color'].';'
            .'border:'.$theme['border_width'].'px '.$theme['border_style'].' '.$theme['border_color'].';'
            .'border-radius:'.$theme['border_radius'].'px;padding:'.$theme['padding'].'px;'
            .'position:relative;width:100%;box-sizing:border-box;'
            .'}';

        // Everything except the watermark/accent-bar layers sits above them.
        // Written as a blanket child rule rather than
        // `> *:not(.doc-watermark)`: dompdf's selector parser has no `:not()`,
        // so a `:not()`-based rule is dropped silently and the watermark ends up
        // painted over the document body in every PDF. The two layers reset
        // themselves (see watermarkCss()/accentBarCss()) so nothing here has to
        // know whether they are rendered.
        $content = $root.' > *{position:relative;z-index:1;}';

        $headings = $root.' .doc-title{font-size:'.$theme['title_font_size'].'px;color:'.$theme['secondary_color'].';font-weight:700;margin:0 0 .4em;}';
        $muted = $root.' .doc-muted{color:'.$theme['muted_color'].';}';
        $name = $root.' .doc-name{color:'.$theme['primary_color'].';font-weight:700;text-decoration:underline;}';
        $number = $root.' .doc-number{color:'.$theme['muted_color'].';font-size:.85em;}';
        $notes = $root.' .doc-notes{background:'.$this->tint($theme['accent_color'], 0.08).';'
            .'border-left:3px solid '.$theme['accent_color'].';padding:10px 12px;text-align:left;'
            .'color:'.$theme['text_color'].';}';
        $signature = $root.' .doc-signature{border-top:1px solid '.$theme['text_color'].';padding-top:6px;text-align:center;color:'.$theme['text_color'].';}';
        $table = $root.' table.doc-table{width:100%;border-collapse:collapse;}';
        $tableCells = $root.' .doc-table th,.doc-table td{border:1px solid '.$theme['border_color'].';padding:8px 10px;text-align:left;}';
        $tableHead = $root.' .doc-table th{background:'.$this->tint($theme['primary_color'], 0.10).';color:'.$theme['secondary_color'].';}';
        $logo = $root.' .doc-logo{max-height:60px;object-fit:contain;margin:0 auto 8px;}';
        $logoLeft = $root.' .doc-logo-left{margin:0 12px 8px 0;float:left;}';
        // Tinted panels: the dompdf templates cannot use var(), so every value
        // they need has to exist as a literal-value class here.
        $panel = $root.' .doc-panel{background:'.$this->tint($theme['accent_color'], 0.06).';'
            .'border:1px solid '.$theme['border_color'].';border-radius:'.$theme['border_radius'].'px;padding:12px 16px;}';
        $panelMuted = $root.' .doc-panel-muted{background:'.$this->tint($theme['muted_color'], 0.08).';}';
        $pass = $root.' .doc-pass{color:'.$this->tint($theme['primary_color'], 1.0).';font-weight:700;}';
        $fail = $root.' .doc-fail{color:#dc2626;font-weight:700;}';

        $templates = [
            'classic' => $root.'{text-align:center;}'
                .$root.' .doc-body{text-align:left;}',
            'modern' => $root.'{box-shadow:0 1px 3px rgba(15,23,42,.12);}'
                .$root.' .doc-title{letter-spacing:1px;text-transform:uppercase;}'
                .$root.' .doc-body{text-align:left;line-height:1.7;}',
            'minimal' => $root.'{border-width:0;border-radius:0;background:transparent;}'
                .$root.' .doc-title{font-weight:400;letter-spacing:2px;}'
                .$root.' .doc-name{text-decoration:none;}',
            'bordered' => $root.'{border-style:double;}'
                .'.doc-inner-frame{position:absolute;top:8px;right:8px;bottom:8px;left:8px;'
                .'border:1px solid '.$theme['accent_color'].';border-radius:'.$theme['border_radius'].'px;'
                .'pointer-events:none;opacity:.6;}',
        ];

        return $base.$content.$headings.$muted.$name.$number.$notes.$signature.$table.$tableCells.$tableHead.$logo.$logoLeft
            .$panel.$panelMuted.$pass.$fail
            .($templates[$theme['template']] ?? $templates['classic']);
    }

    /**
     * The accent bar is driven by `accent_bar` in the theme, not by
     * `:not(:empty)` on an empty element — dompdf does not support that
     * selector, so an empty bar would simply never appear in a PDF.
     *
     * @param  array<string, mixed>  $theme
     */
    private function accentBarCss(array $theme): string
    {
        if ($theme['accent_bar'] === 'none') {
            return '.doc-accent-bar{display:none;}';
        }

        $positions = [
            'top' => 'top:0;left:0;right:0;',
            'bottom' => 'bottom:0;left:0;right:0;',
            'left' => 'top:0;bottom:0;left:0;width:6px;',
            'right' => 'top:0;bottom:0;right:0;width:6px;',
        ];
        $position = $positions[$theme['accent_bar']] ?? 'top:0;left:0;right:0;';
        $height = in_array($theme['accent_bar'], ['left', 'right'], true) ? '100%' : '6px';

        return '.doc-accent-bar{display:block;position:absolute;'.$position.'height:'.$height.';z-index:2;'
            .'background:'.$theme['accent_color'].';}';
    }

    /** @param array<string, mixed> $theme */
    private function pageCss(array $theme): string
    {
        $sizes = [
            'a4' => '210mm',
            'letter' => '215.9mm',
            'legal' => '215.9mm',
            'credit-card' => '85.6mm',
        ];
        $width = $sizes[$theme['page_size']] ?? '210mm';

        return '@page{size:'.$theme['orientation'].' '.$width.';margin:10mm;}';
    }

    /**
     * @param  array<string, mixed>  $watermark
     */
    private function watermarkCss(array $watermark, string $root): string
    {
        if (! $watermark['enabled']) {
            return '';
        }

        // The first rule lifts the layer back out of the `> *` stacking reset
        // emitted by templateCss().
        $layer = $root.' > .doc-watermark{position:absolute;z-index:0;top:0;right:0;bottom:0;left:0;'
            .'display:flex;align-items:center;justify-content:center;'
            .'pointer-events:none;overflow:hidden;}';
        $item = '.doc-watermark-item{opacity:'.$watermark['opacity'].';color:'.$watermark['color'].';'
            .'font-size:'.$watermark['font_size'].'px;font-family:'.$watermark['font_family'].';'
            .'font-weight:700;white-space:nowrap;text-align:center;line-height:1.1;}';

        $positionCss = match ($watermark['position']) {
            'center' => '.doc-watermark-item{transform:rotate(0deg);}',
            'top' => '.doc-watermark{align-items:flex-start;padding-top:8%;}.doc-watermark-item{transform:rotate('.$watermark['rotation'].'deg);}',
            'bottom' => '.doc-watermark{align-items:flex-end;padding-bottom:8%;}.doc-watermark-item{transform:rotate('.$watermark['rotation'].'deg);}',
            'tile' => '.doc-watermark{flex-wrap:wrap;align-content:center;gap:6% 8%;}.doc-watermark-item{'
                .'transform:rotate('.$watermark['rotation'].'deg);font-size:'.max(10, (int) ($watermark['font_size'] * 0.5)).'px;}',
            default => '.doc-watermark-item{transform:rotate('.$watermark['rotation'].'deg);}',
        };

        $content = $watermark['type'] === 'text'
            ? ''
            : '.doc-watermark-item img{max-height:60%;max-width:60%;}';

        return $layer.$item.$positionCss.$content;
    }

    // ------------------------------------------------------------- primitives

    private function color(mixed $value, string $fallback): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) ? strtolower($value) : $fallback;
    }

    /**
     * A translucent version of a hex colour, e.g. `tint('#2563eb', 0.08)`.
     * Used where `color-mix()` would be the CSS-native answer — dompdf has no
     * support for it, and the raw RGB channels are what the PDF engine needs.
     */
    private function tint(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 && strlen($hex) !== 8) {
            return 'rgba(37,99,235,'.$this->alpha($alpha).')';
        }

        [$r, $g, $b] = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        return 'rgba('.(int) $r.','.(int) $g.','.(int) $b.','.$this->alpha($alpha).')';
    }

    /** A short, locale-independent alpha literal ("1", "0.5", "0.08"). */
    private function alpha(float $alpha): string
    {
        return rtrim(rtrim(number_format($alpha, 3, '.', ''), '0'), '.');
    }

    private function clampInt(mixed $value, int $min, int $max, int $default): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }

    private function clampFloat(mixed $value, float $min, float $max, float $default): float
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return round(max($min, min($max, (float) $value)), 3);
    }

    private function flag(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private function line(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(strip_tags($value));

        return $value === '' ? null : Str::limit($value, $max, '');
    }

    /** Only relative upload paths — no protocol, no traversal. */
    private function uploadPath(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || preg_match('#^(https?:|//|data:)#i', $value) || str_contains($value, '..')) {
            return null;
        }

        return Str::limit(ltrim($value, '/'), 255, '');
    }

    /**
     * The site's own logo, used when the watermark type is `logo` and no
     * dedicated watermark image was uploaded. Resolving it here (instead of in
     * the view) is what keeps the watermark partial free of extra variables,
     * and therefore byte-identical across products.
     *
     * `logo_path` (the stored upload path) is preferred over the `logo_url`
     * accessor on purpose: the accessor returns an absolute `url()` built from
     * the current host, which is neither stable across the four products nor
     * fetchable by the PDF renderer.
     */
    private function siteLogoPath(): ?string
    {
        try {
            $settings = \App\Models\WebsiteSetting::getSettings();
            $logo = $settings->logo_path ?? null;
        } catch (\Throwable) {
            return null;
        }

        if (is_string($logo) && $logo !== '') {
            $path = $this->uploadPath($logo);
            if ($path !== null) {
                return $path;
            }
        }

        try {
            $url = $settings->logo_url ?? null;
        } catch (\Throwable) {
            return null;
        }

        return is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('document_designs');
        } catch (\Throwable) {
            return false;
        }
    }
}
