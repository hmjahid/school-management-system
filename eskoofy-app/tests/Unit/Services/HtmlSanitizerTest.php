<?php

namespace Tests\Unit\Services;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_strips_script_and_iframe(): void
    {
        $out = (new HtmlSanitizer)->sanitize('<p>hi<script>alert(1)</script><iframe src="x"></iframe></p>');
        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('<iframe', $out);
        $this->assertStringContainsString('hi', $out);
    }

    public function test_removes_javascript_hrefs(): void
    {
        $out = (new HtmlSanitizer)->sanitize('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringContainsString('>x</a>', $out);
    }

    public function test_removes_event_handlers(): void
    {
        $out = (new HtmlSanitizer)->sanitize('<img src="/a.png" onerror="alert(1)"><b onclick="x()">b</b>');
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringContainsString('<b>', $out);
    }

    public function test_keeps_safe_markup(): void
    {
        $out = (new HtmlSanitizer)->sanitize('<p>Hello <b>bold</b> <a href="https://example.com">link</a></p>');
        $this->assertStringContainsString('<b>bold</b>', $out);
        $this->assertStringContainsString('href="https://example.com"', $out);
        $this->assertStringContainsString('<p>', $out);
    }
}
