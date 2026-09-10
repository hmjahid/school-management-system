<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Session;
use Tests\TestCase;

class SessionTest extends TestCase
{
    public function test_singleton_returns_same_instance(): void
    {
        $a = Session::getInstance();
        $b = Session::getInstance();
        $this->assertSame($a, $b);
    }

    public function test_set_and_get(): void
    {
        $session = Session::getInstance();
        $session->set('foo', 'bar');
        $this->assertSame('bar', $session->get('foo'));
    }

    public function test_get_returns_default_when_missing(): void
    {
        $session = Session::getInstance();
        $this->assertSame('default', $session->get('missing', 'default'));
        $this->assertNull($session->get('missing'));
    }

    public function test_has(): void
    {
        $session = Session::getInstance();
        $this->assertFalse($session->has('key'));
        $session->set('key', 'value');
        $this->assertTrue($session->has('key'));
    }

    public function test_remove(): void
    {
        $session = Session::getInstance();
        $session->set('key', 'value');
        $this->assertTrue($session->has('key'));

        $session->remove('key');
        $this->assertFalse($session->has('key'));
    }

    public function test_flash_and_get_flash(): void
    {
        $session = Session::getInstance();
        $session->flash('success', 'It worked!');

        $this->assertSame('It worked!', $session->getFlash('success'));
        $this->assertNull($session->getFlash('success'));
    }

    public function test_get_flash_returns_default(): void
    {
        $session = Session::getInstance();
        $this->assertSame('fallback', $session->getFlash('missing', 'fallback'));
    }

    public function test_flash_all(): void
    {
        $session = Session::getInstance();
        $session->flash('a', 1);
        $session->flash('b', 2);

        $all = $session->flashAll();
        $this->assertSame(['a' => 1, 'b' => 2], $all);
        $this->assertNull($session->getFlash('a'));
        $this->assertNull($session->getFlash('b'));
    }

    public function test_store_complex_values(): void
    {
        $session = Session::getInstance();
        $session->set('array', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $session->get('array'));

        $session->set('nested', ['key' => ['deep' => true]]);
        $this->assertSame(true, $session->get('nested')['key']['deep']);
    }
}
