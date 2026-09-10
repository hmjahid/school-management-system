<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class PwaTest extends TestCase
{
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicDir = dirname(__DIR__, 2) . '/public';
    }

    public function test_manifest_is_valid_json_with_required_fields(): void
    {
        $manifestPath = $this->publicDir . '/manifest.json';
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);

        $this->assertArrayHasKey('name', $manifest);
        $this->assertNotEmpty($manifest['name']);
        $this->assertSame('/', $manifest['start_url'] ?? null);
        $this->assertSame('/', $manifest['scope'] ?? null);
        $this->assertSame('standalone', $manifest['display'] ?? null);
        $this->assertArrayHasKey('icons', $manifest);
        $this->assertIsArray($manifest['icons']);

        $purposes = [];
        $sizes = [];
        foreach ($manifest['icons'] as $icon) {
            $sizes[] = $icon['sizes'] ?? '';
            $purposes[] = $icon['purpose'] ?? 'any';
        }

        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', $purposes);
    }

    public function test_all_icon_files_exist(): void
    {
        foreach (['icon-192.png', 'icon-512.png', 'maskable-512.png', 'apple-touch-icon.png'] as $file) {
            $this->assertFileExists($this->publicDir . '/icons/' . $file, "Missing icon: {$file}");
        }
    }

    public function test_favicon_svg_exists(): void
    {
        $this->assertFileExists($this->publicDir . '/favicon.svg');
    }

    public function test_offline_page_exists(): void
    {
        $this->assertFileExists($this->publicDir . '/offline.html');
    }

    public function test_service_worker_precaches_public_shell_only(): void
    {
        $sw = (string) file_get_contents($this->publicDir . '/sw.js');

        $this->assertStringContainsString('skipWaiting', $sw);
        $this->assertStringContainsString('clients.claim', $sw);

        $precache = $this->extractPrecache($sw);

        // Public pages must be in the precache.
        $this->assertContains('/', $precache);
        $this->assertContains('/blog', $precache);
        $this->assertContains('/pricing', $precache);
        $this->assertContains('/features', $precache);
        $this->assertContains('/about', $precache);
        $this->assertContains('/contact', $precache);

        // Excluded paths must not be precached.
        foreach (['/admin', '/api', '/account', '/checkout', '/login', '/register'] as $excluded) {
            $this->assertNotContains($excluded, $precache, "PWA precache leaks '{$excluded}'");
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractPrecache(string $sw): array
    {
        if (!preg_match('/const\s+PRECACHE\s*=\s*\[(.+?)\];/s', $sw, $m)) {
            $this->fail('Could not find PRECACHE constant in sw.js');
        }

        preg_match_all("/'([^']*)'/", $m[1], $items);

        return $items[1];
    }

    public function test_layouts_link_pwa_assets(): void
    {
        $main = (string) file_get_contents(dirname(__DIR__, 2) . '/views/layouts/main.php');
        $admin = (string) file_get_contents(dirname(__DIR__, 2) . '/views/layouts/admin.php');

        $this->assertStringContainsString('rel="manifest"', $main);
        $this->assertStringContainsString('rel="manifest"', $admin);
        $this->assertStringContainsString('/icons/apple-touch-icon.png', $main);
        $this->assertStringContainsString('register-sw.js', $main);
        $this->assertStringNotContainsString('register-sw.js', $admin);
    }
}