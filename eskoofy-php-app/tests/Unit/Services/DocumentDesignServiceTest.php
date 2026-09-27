<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DocumentDesignService;
use Tests\TestCase;

class DocumentDesignServiceTest extends TestCase
{
    private DocumentDesignService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentDesignService();
    }

    public function test_types_and_templates(): void
    {
        $this->assertContains('certificate', DocumentDesignService::types());
        $this->assertContains('id_card', DocumentDesignService::types());
        $this->assertTrue(DocumentDesignService::isType('marksheet'));
        $this->assertFalse(DocumentDesignService::isType('nonsense'));
        $this->assertTrue(DocumentDesignService::isTemplate('bordered'));
        $this->assertFalse(DocumentDesignService::isTemplate('../../etc/passwd'));
    }

    public function test_theme_falls_back_to_config_defaults_when_no_design_row(): void
    {
        // No document_designs table exists in the test DB, so the config
        // defaults must win.
        $theme = $this->service->theme('certificate');

        $this->assertSame('certificate', $theme['document_type']);
        $this->assertSame('classic', $theme['template']);
        $this->assertSame('#1e40af', $theme['primary_color']);
        $this->assertFalse($theme['has_custom_design']);
        $this->assertSame('Certificate', $theme['name']);
    }

    public function test_sanitize_theme_clamps_values(): void
    {
        $theme = $this->service->sanitizeTheme([
            'primary_color' => 'not-a-color',
            'base_font_size' => 999,
            'title_font_size' => 0,
            'border_style' => 'groovy',
            'accent_bar' => 'weird',
            'show_logo' => '0',
        ]);

        $this->assertSame('#1e40af', $theme['primary_color']);
        $this->assertSame(32, $theme['base_font_size']);
        $this->assertSame(10, $theme['title_font_size']);
        $this->assertSame('solid', $theme['border_style']);
        $this->assertSame('none', $theme['accent_bar']);
        $this->assertFalse($theme['show_logo']);
    }

    public function test_watermark_merges_global_and_per_type_switches(): void
    {
        // Global enabled, per-type true → on.
        $wm = $this->service->sanitizeWatermark(['enabled' => true, 'type' => 'text', 'text' => 'X'], 'certificate');
        $this->assertTrue($wm['enabled']);

        // Type text fallback uses the school name.
        $this->assertNotNull($wm['text']);
    }

    public function test_css_for_is_dompdf_safe(): void
    {
        $css = $this->service->cssFor(
            $this->service->sanitizeTheme(['primary_color' => '#112233']),
            $this->service->sanitizeWatermark(['enabled' => true, 'type' => 'text', 'text' => 'W']),
            ''
        );

        // No var() / calc() / color-mix() / :not() — dompdf cannot parse them.
        $this->assertStringNotContainsString('var(', $css);
        $this->assertStringNotContainsString('calc(', $css);
        $this->assertStringNotContainsString('color-mix(', $css);
        $this->assertStringNotContainsString(':not(', $css);
    }

    public function test_css_includes_watermark_rules_when_enabled(): void
    {
        $css = $this->service->cssFor(
            $this->service->sanitizeTheme([]),
            $this->service->sanitizeWatermark(['enabled' => true, 'type' => 'text', 'text' => 'WATER']),
            ''
        );

        $this->assertStringContainsString('.doc-watermark', $css);
    }

    public function test_sanitize_css_strips_executable_bits(): void
    {
        $clean = $this->service->sanitizeCss(
            '.x{color:red;}@import url(evil.css);<script>alert(1)</script>.y{background:url(https://evil/x.png)}'
        );

        $this->assertStringNotContainsString('@import', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('https:', $clean);
        $this->assertStringContainsString('.x{color:red;}', $clean);
    }

    public function test_custom_css_can_override(): void
    {
        $css = $this->service->cssFor(
            $this->service->sanitizeTheme([]),
            $this->service->sanitizeWatermark(['enabled' => false]),
            '.doc-title{color:#ff0000;}'
        );

        $this->assertStringContainsString('.doc-title{color:#ff0000;}', $css);
    }
}