<?php

namespace Tests\Unit\Services;

use App\Models\DocumentDesign;
use App\Services\DocumentDesignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentDesignServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): DocumentDesignService
    {
        return new DocumentDesignService;
    }

    // ------------------------------------------------------------------- types

    #[Test]
    public function all_five_document_types_are_supported(): void
    {
        $this->assertSame(
            ['certificate', 'testimonial', 'marksheet', 'admit_card', 'id_card'],
            DocumentDesignService::types()
        );

        foreach (DocumentDesignService::types() as $type) {
            $this->assertTrue(DocumentDesignService::isType($type));
        }
        $this->assertFalse(DocumentDesignService::isType('invoice'));
        $this->assertFalse(DocumentDesignService::isType(null));
    }

    #[Test]
    public function an_unknown_type_falls_back_to_the_configured_defaults(): void
    {
        $theme = $this->service()->theme('invoice');

        $this->assertSame('solid', $theme['border_style'], 'unknown types must not inherit another type\'s branding');
    }

    // ------------------------------------------------------------------ design

    #[Test]
    public function a_stored_design_overrides_the_config_default(): void
    {
        DocumentDesign::query()->create([
            'document_type' => 'certificate',
            'name' => 'Modern blue',
            'template' => 'modern',
            'settings' => ['primary_color' => '#ff0000', 'border_style' => 'dotted'],
            'is_default' => true,
            'is_active' => true,
        ]);

        $theme = $this->service()->theme('certificate');

        $this->assertSame('modern', $theme['template']);
        $this->assertSame('#ff0000', $theme['primary_color']);
        $this->assertSame('dotted', $theme['border_style']);
        // Untouched keys still come from the bd profile config.
        $this->assertSame('#ffffff', $theme['background_color']);
        $this->assertTrue($theme['has_custom_design']);
    }

    #[Test]
    public function an_inactive_design_is_ignored(): void
    {
        DocumentDesign::query()->create([
            'document_type' => 'certificate',
            'name' => 'Disabled',
            'template' => 'bordered',
            'settings' => ['primary_color' => '#00ff00'],
            'is_default' => true,
            'is_active' => false,
        ]);

        $this->assertFalse($this->service()->theme('certificate')['has_custom_design']);
    }

    // ----------------------------------------------------------------- clamping

    #[Test]
    public function out_of_range_values_are_clamped(): void
    {
        $theme = $this->service()->sanitizeTheme([
            'base_font_size' => 999,
            'title_font_size' => -5,
            'border_width' => 100,
            'border_radius' => -1,
            'padding' => 100000,
            'opacity' => 5,
        ]);

        $this->assertSame(32, $theme['base_font_size']);
        $this->assertSame(10, $theme['title_font_size']);
        $this->assertSame(12, $theme['border_width']);
        $this->assertSame(0, $theme['border_radius']);
        $this->assertSame(160, $theme['padding']);

        $watermark = $this->service()->sanitizeWatermark(['opacity' => 9, 'rotation' => 400, 'font_size' => 0]);
        $this->assertSame(1.0, $watermark['opacity']);
        $this->assertSame(180, $watermark['rotation']);
        $this->assertSame(8, $watermark['font_size']);
    }

    #[Test]
    public function a_non_numeric_number_falls_back_to_the_default_not_the_minimum(): void
    {
        $theme = $this->service()->sanitizeTheme(['base_font_size' => 'huge', 'title_font_size' => null]);

        $this->assertSame(14, $theme['base_font_size']);
        $this->assertSame(20, $theme['title_font_size']);
    }

    #[Test]
    public function invalid_colours_and_enums_fall_back(): void
    {
        $theme = $this->service()->sanitizeTheme([
            'primary_color' => 'red; background: url(evil)',
            'font_family' => 'Comic Sans',
            'border_style' => 'wobbly',
            'page_size' => 'gigantic',
            'orientation' => 'sideways',
            'accent_bar' => 'diagonal',
        ]);

        $this->assertSame('#1e40af', $theme['primary_color']);
        $this->assertSame('inherit', $theme['font_family']);
        $this->assertSame('solid', $theme['border_style']);
        $this->assertSame('a4', $theme['page_size']);
        $this->assertSame('portrait', $theme['orientation']);
        $this->assertSame('none', $theme['accent_bar']);
    }

    #[Test]
    public function the_whitelisted_font_families_survive(): void
    {
        $theme = $this->service()->sanitizeTheme(['font_family' => 'Georgia, serif']);

        $this->assertSame('Georgia, serif', $theme['font_family']);
    }

    // ------------------------------------------------------------ custom css

    #[Test]
    public function valid_custom_css_keeps_its_declarations(): void
    {
        $css = $this->service()->sanitizeCss('.doc-body { color: #ff0000; font-size: 18px; }');

        $this->assertStringContainsString('color: #ff0000;', $css);
        $this->assertStringContainsString('font-size: 18px;', $css);
        $this->assertStringContainsString('{', $css);
        $this->assertStringContainsString('}', $css);
    }

    #[Test]
    public function custom_css_cannot_break_out_of_the_style_element(): void
    {
        $css = $this->service()->sanitizeCss('</style><script>alert(1)</script>.x{color:red}');

        $this->assertStringNotContainsString('<', $css);
        $this->assertStringNotContainsString('>', $css);
        $this->assertStringNotContainsString('script', strtolower($css));
        $this->assertStringContainsString('.x{color:red}', $css);
    }

    #[Test]
    public function custom_css_drops_remote_assets_and_executable_constructs(): void
    {
        $sanitizer = $this->service();

        $this->assertStringContainsString('none', $sanitizer->sanitizeCss('.a{background:url(https://evil.test/x.png)}'));
        $this->assertStringContainsString('none', $sanitizer->sanitizeCss('.a{background:url(//evil.test/x.png)}'));
        $this->assertStringContainsString('none', $sanitizer->sanitizeCss('.a{background:url(../../secret)}'));
        $this->assertStringNotContainsString('@import', $sanitizer->sanitizeCss('@import url(x.css);.a{color:red}'));
        $this->assertStringNotContainsString('javascript:', strtolower($sanitizer->sanitizeCss('.a{background:url(javascript:alert(1))}')));
    }

    #[Test]
    public function a_local_relative_url_is_allowed(): void
    {
        $this->assertStringContainsString(
            'url(uploads/brand.png)',
            $this->service()->sanitizeCss('.a{background:url("uploads/brand.png")}')
        );
    }

    #[Test]
    public function unbalanced_braces_reject_the_whole_stylesheet(): void
    {
        $this->assertSame('', $this->service()->sanitizeCss('.a { color: red;'));
    }

    // ---------------------------------------------------------------- watermark

    #[Test]
    public function the_watermark_is_off_unless_enabled(): void
    {
        $watermark = $this->service()->watermark('certificate');

        $this->assertFalse($watermark['enabled']);
        $this->assertFalse($this->service()->hasWatermark('certificate'));
    }

    #[Test]
    public function a_design_row_can_enable_the_watermark_per_document(): void
    {
        DocumentDesign::query()->create([
            'document_type' => 'marksheet',
            'name' => 'Watermarked',
            'watermark' => [
                'enabled' => true,
                'type' => 'text',
                'text' => 'CONFIDENTIAL',
                'opacity' => 0.25,
                'position' => 'tile',
            ],
            'is_default' => true,
            'is_active' => true,
        ]);

        $service = $this->service();
        $this->assertTrue($service->hasWatermark('marksheet'));
        $this->assertSame('CONFIDENTIAL', $service->watermark('marksheet')['text']);

        // Another type is unaffected.
        $this->assertFalse($service->hasWatermark('certificate'));
    }

    #[Test]
    public function a_logo_watermark_without_an_upload_falls_back_to_the_site_logo(): void
    {
        \App\Models\WebsiteSetting::query()->create([
            'school_name' => 'Greenfield Academy',
            'established_year' => 2000,
            'address' => '1 Main Street',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'postal_code' => '1207',
            'phone' => '+8801000000000',
            'email' => 'office@greenfield.test',
            'logo_path' => 'uploads/school/logo.png',
        ]);

        $watermark = $this->service()->sanitizeWatermark(['type' => 'logo', 'enabled' => true]);

        $this->assertSame('uploads/school/logo.png', $watermark['image_path']);
    }

    #[Test]
    public function a_logo_watermark_ignores_the_site_logo_when_one_was_uploaded(): void
    {
        \App\Models\WebsiteSetting::query()->create([
            'school_name' => 'Greenfield Academy',
            'established_year' => 2000,
            'address' => '1 Main Street',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'postal_code' => '1207',
            'phone' => '+8801000000000',
            'email' => 'office@greenfield.test',
            'logo_path' => 'uploads/school/logo.png',
        ]);

        $watermark = $this->service()->sanitizeWatermark([
            'type' => 'logo',
            'enabled' => true,
            'image_path' => 'documents/watermark.png',
        ]);

        $this->assertSame('documents/watermark.png', $watermark['image_path']);
    }

    #[Test]
    public function a_watermark_image_path_can_never_escape_the_upload_directory(): void
    {
        $service = $this->service();

        $this->assertNull($service->sanitizeWatermark(['type' => 'image', 'image_path' => '../../config/app.php'])['image_path']);
        $this->assertNull($service->sanitizeWatermark(['type' => 'image', 'image_path' => 'https://evil.test/x.png'])['image_path']);
        $this->assertNull($service->sanitizeWatermark(['type' => 'image', 'image_path' => 'data:image/png;base64,AA'])['image_path']);
    }

    #[Test]
    public function the_watermark_text_falls_back_to_the_school_name(): void
    {
        config()->set('app.name', 'Greenfield Academy');

        $watermark = $this->service()->sanitizeWatermark(['type' => 'text', 'enabled' => true]);

        $this->assertSame('Greenfield Academy', $watermark['text']);
    }

    // ---------------------------------------------------------------------- css

    #[Test]
    public function generated_css_uses_literal_values_so_dompdf_renders_it(): void
    {
        config()->set('eskoolfy.documents.defaults.marksheet', [
            'primary_color' => '#112233', 'accent_color' => '#445566', 'border_color' => '#778899',
            'template' => 'modern', 'border_width' => 2, 'border_style' => 'solid',
        ]);
        DocumentDesignService::flushCache();
        cache()->flush();

        $css = $this->service()->css('marksheet');

        // dompdf supports none of these, and a design that silently loses all of
        // its colours in the PDF is worse than one that never applied.
        $this->assertStringNotContainsString('var(--', str_replace(['--doc-primary:#112233'], '', $css));
        $this->assertStringNotContainsString('calc(', $css);
        $this->assertStringNotContainsString('color-mix(', $css);
        $this->assertStringNotContainsString(':not(', $css);

        $this->assertStringContainsString('color:#112233', $css);
        $this->assertStringContainsString('background:#ffffff', $css);
        $this->assertStringContainsString('border:2px solid #778899', $css);
        $this->assertStringContainsString('rgba(68,85,102,', $css);
    }

    #[Test]
    public function the_css_root_selector_is_scoped_to_the_document_type(): void
    {
        $css = $this->service()->css('id_card');

        $this->assertStringContainsString('.doc-root.doc-id_card{', $css);
        $this->assertStringNotContainsString('.doc-root.doc-certificate{', $css);
    }

    #[Test]
    public function the_accent_bar_is_hidden_when_set_to_none(): void
    {
        config()->set('eskoolfy.documents.defaults.admit_card', [
            'template' => 'modern', 'accent_bar' => 'none', 'primary_color' => '#1e40af',
        ]);
        DocumentDesignService::flushCache();
        cache()->flush();

        $this->assertStringContainsString('.doc-accent-bar{display:none;}', $this->service()->css('admit_card'));
    }

    #[Test]
    public function a_disabled_watermark_emits_no_watermark_css(): void
    {
        $this->assertStringNotContainsString('.doc-watermark', $this->service()->css('certificate'));
    }

    #[Test]
    public function an_enabled_watermark_emits_its_position_and_opacity(): void
    {
        DocumentDesign::query()->create([
            'document_type' => 'testimonial',
            'name' => 'Tiled',
            'watermark' => ['enabled' => true, 'text' => 'DRAFT', 'opacity' => 0.3, 'position' => 'tile', 'rotation' => 30],
            'is_default' => true,
            'is_active' => true,
        ]);
        DocumentDesignService::flushCache();
        cache()->flush();

        $css = $this->service()->css('testimonial');

        $this->assertStringContainsString('opacity:0.3', $css);
        $this->assertStringContainsString('rotate(30deg)', $css);
        $this->assertStringContainsString('flex-wrap:wrap', $css);
    }

    #[Test]
    public function custom_css_is_appended_last_so_it_can_override_the_design(): void
    {
        DocumentDesign::query()->create([
            'document_type' => 'certificate',
            'name' => 'Custom',
            'custom_css' => '.doc-root.doc-certificate{background:#ff00ff;}',
            'is_default' => true,
            'is_active' => true,
        ]);
        DocumentDesignService::flushCache();
        cache()->flush();

        $css = $this->service()->css('certificate');

        $this->assertStringEndsWith('.doc-root.doc-certificate{background:#ff00ff;}', trim($css));
    }

    #[Test]
    public function the_page_rule_matches_the_configured_paper(): void
    {
        config()->set('eskoolfy.documents.defaults.id_card', [
            'template' => 'modern', 'page_size' => 'credit-card', 'orientation' => 'landscape',
        ]);
        DocumentDesignService::flushCache();
        cache()->flush();

        $this->assertStringContainsString('@page{size:landscape 85.6mm;', $this->service()->css('id_card'));
    }

    // ----------------------------------------------------------------- context

    #[Test]
    public function the_render_context_carries_everything_a_view_needs(): void
    {
        $context = $this->service()->context('certificate');

        $this->assertSame(['type', 'theme', 'watermark', 'custom_css', 'css'], array_keys($context));
        $this->assertSame('certificate', $context['type']);
        $this->assertNotSame('', $context['css']);
    }

    #[Test]
    public function the_context_works_before_the_migrations_have_run(): void
    {
        \Illuminate\Support\Facades\Schema::drop('document_designs');

        $theme = $this->service()->theme('certificate');

        $this->assertFalse($theme['has_custom_design']);
        $this->assertSame('classic', $theme['template']);
    }

    // ------------------------------------------------------------------ helpers

    #[Test]
    public function the_view_helpers_resolve_through_the_container(): void
    {
        $context = document_context('marksheet');
        $watermark = document_watermark('marksheet');

        $this->assertSame('marksheet', $context['type']);
        $this->assertArrayHasKey('enabled', $watermark);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function templateProvider(): array
    {
        return [
            'classic' => ['classic', '.doc-body{text-align:left;}'],
            'modern' => ['modern', 'text-transform:uppercase'],
            'minimal' => ['minimal', 'border-width:0'],
            'bordered' => ['bordered', '.doc-inner-frame'],
        ];
    }

    #[Test]
    #[DataProvider('templateProvider')]
    public function each_template_emits_its_own_rules(string $template, string $expected): void
    {
        config()->set('eskoolfy.documents.defaults.certificate', ['template' => $template]);
        DocumentDesignService::flushCache();
        cache()->flush();

        $this->assertStringContainsString($expected, $this->service()->css('certificate'));
    }

    #[Test]
    public function the_layer_ordering_is_expressed_without_not_selectors(): void
    {
        DocumentDesign::query()->create([
            'document_type' => 'marksheet',
            'name' => 'Layered',
            'settings' => ['accent_bar' => 'top'],
            'watermark' => ['enabled' => true, 'text' => 'DRAFT'],
            'is_default' => true,
            'is_active' => true,
        ]);
        DocumentDesignService::flushCache();
        cache()->flush();

        $css = $this->service()->css('marksheet');

        // The content blanket rule, then the two layers lifting themselves back
        // out of it. dompdf drops `:not()`, so the reset has to be explicit.
        $this->assertStringContainsString('.doc-root.doc-marksheet > *{position:relative;z-index:1;}', $css);
        $this->assertStringContainsString('.doc-root.doc-marksheet > .doc-watermark{position:absolute;z-index:0;', $css);
        $this->assertStringContainsString('z-index:2;', $css, 'the accent bar must sit above the content');

        $this->assertLessThan(
            strpos($css, '.doc-root.doc-marksheet > .doc-watermark{'),
            strpos($css, '.doc-root.doc-marksheet > *{'),
            'the content rule must be emitted first so the layer resets win'
        );
    }

    #[Test]
    public function an_edit_is_picked_up_by_the_cache_flush_a_save_triggers(): void
    {
        $design = DocumentDesign::query()->create([
            'document_type' => 'certificate',
            'name' => 'Modern',
            'template' => 'modern',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertSame('modern', $this->service()->theme('certificate')['template']);

        $design->update(['template' => 'bordered']);
        DocumentDesignService::flushCache();

        $this->assertSame('bordered', $this->service()->theme('certificate')['template']);
    }
}
