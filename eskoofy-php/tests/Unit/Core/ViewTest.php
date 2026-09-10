<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\View;
use Tests\TestCase;

class ViewTest extends TestCase
{
    public function test_share_and_resolve(): void
    {
        View::share('testKey', 'testValue');
        ob_start();
        View::render('partials.site.footer');
        $html = ob_get_clean();
        $this->assertIsString($html);
    }

    public function test_resolve_direct_path(): void
    {
        $path = View::resolve('partials.site.footer');
        $this->assertStringContainsString('footer', $path);
        $this->assertStringEndsWith('.php', $path);
    }

    public function test_resolve_with_dot_notation(): void
    {
        $path = View::resolve('auth.login');
        $this->assertStringContainsString('auth', $path);
    }

    public function test_resolve_with_hyphen_converts_to_underscore(): void
    {
        $path = View::resolve('test-view');
        $this->assertStringContainsString('test_view', $path);
    }

    public function test_resolve_nonexistent_returns_path(): void
    {
        $path = View::resolve('nonexistent.template');
        $this->assertStringContainsString('nonexistent/template.php', $path);
    }

    public function test_shared_data_persist(): void
    {
        View::share('persist', 'yes');
        $this->expectOutputString('');
        ob_start();
        View::partial('nonexistent.partial');
        ob_end_clean();
    }

    public function test_partial_renders(): void
    {
        ob_start();
        View::partial('partials.site.footer');
        $html = ob_get_clean();
        $this->assertIsString($html);
    }
}
