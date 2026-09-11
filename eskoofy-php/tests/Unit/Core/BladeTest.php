<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Blade;
use Tests\TestCase;

/**
 * Locks in the Blade-compatible engine (app/Core/Blade.php) that lets
 * eskoofy-php render the exact Laravel Blade templates from eskoofy-app.
 */
class BladeTest extends TestCase
{
    private string $viewRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewRoot = sys_get_temp_dir() . '/eskoofy_blade_test_' . bin2hex(random_bytes(4));
        mkdir($this->viewRoot, 0777, true);
        Blade::$viewRoot = $this->viewRoot;
        Blade::$compilePath = $this->viewRoot . '/compiled';
        @mkdir(Blade::$compilePath, 0777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach (glob($this->viewRoot . '/*') ?: [] as $f) {
            if (is_dir($f) && str_contains($f, 'compiled')) {
                array_map('unlink', glob($f . '/*') ?: []);
                @rmdir($f);
            } else {
                @unlink($f);
            }
        }
        @rmdir($this->viewRoot);
    }

    public function test_compiles_escaped_and_raw_echoes(): void
    {
        $php = Blade::compileString('Hello {{ $name }} and {!! $html !!} and @{{ literal }}');
        $this->assertStringContainsString('<?php echo e($name); ?>', $php);
        $this->assertStringContainsString('$html; ?>', $php);
        $this->assertStringContainsString('{{ literal }}', $php);
    }

    public function test_compiles_nested_echo_literal(): void
    {
        $php = Blade::compileString("{{ __('Use {{amount}} here.') }}");
        $this->assertStringContainsString("echo e(__('Use {{amount}} here.'))", $php);
    }

    public function test_compiles_conditionals_and_loops(): void
    {
        $php = Blade::compileString('@if($a) A @elseif($b) B @else C @endif');
        $this->assertStringContainsString('<?php if($a): ?>', $php);
        $this->assertStringContainsString('<?php elseif($b): ?>', $php);
        $this->assertStringContainsString('<?php else: ?>', $php);

        $loop = Blade::compileString('@foreach($items as $item) {{ $item }} @endforeach');
        $this->assertStringContainsString('foreach((is_iterable($__currentLoopData) ? $__currentLoopData : []) as $item):', $loop);
        $this->assertStringContainsString('$loop = $__env->getLastLoop();', $loop);
    }

    public function test_compiles_switch_like_laravel(): void
    {
        $php = Blade::compileString('@switch($x) @case(1) one @break @default other @endswitch');
        $this->assertStringContainsString('<?php switch($x):', $php);
        $this->assertStringContainsString('case 1: ?>', $php);
        $this->assertStringContainsString('<?php default: ?>', $php);
        $this->assertStringContainsString('<?php endswitch; ?>', $php);
    }

    public function test_renders_layout_with_sections(): void
    {
        file_put_contents($this->viewRoot . '/layout.blade.php', '<html>@yield("title", "Default")|@yield("content")|@stack("js")</html>');
        file_put_contents($this->viewRoot . '/page.blade.php', "@extends('layout')\n@section('title', 'My Page')\n@section('content')Hello {{ \$who }}@endsection\n@push('js')<script>alert(1)</script>@endpush");

        $html = Blade::render('page', ['who' => 'World']);
        $this->assertStringContainsString('<html>My Page|Hello World|<script>alert(1)</script></html>', $html);
    }

    public function test_renders_anonymous_component_with_props_slot_and_attributes(): void
    {
        mkdir($this->viewRoot . '/components', 0777, true);
        file_put_contents($this->viewRoot . '/components/badge.blade.php', "@props(['variant' => 'default'])\n<span {{ \$attributes->merge(['class' => 'badge-'.\$variant]) }}>{{ \$slot }}</span>");
        file_put_contents($this->viewRoot . '/uses.blade.php', "<x-badge :variant=\"'brand'\" data-x=\"1\">Hi</x-badge>");

        $html = Blade::render('uses');
        $this->assertStringContainsString('<span class="badge-brand" data-x="1">Hi</span>', html_entity_decode($html));
    }

    public function test_renders_foreach_with_loop_variable(): void
    {
        file_put_contents($this->viewRoot . '/loop.blade.php', '@foreach($items as $i => $item){{ $loop->first ? "[" : "" }}{{ $item }}@endforeach');

        $html = Blade::render('loop', ['items' => ['a', 'b']]);
        $this->assertSame('[ab', $html);
    }

    public function test_renders_auth_and_can_guards(): void
    {
        file_put_contents($this->viewRoot . '/guards.blade.php', '@auth authed @endauth @guest guest @endguest @can("manage") allowed @endcan');

        $html = Blade::render('guards');
        $this->assertStringContainsString('guest', $html);
    }
}