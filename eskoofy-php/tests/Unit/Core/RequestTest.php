<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Request;
use Tests\TestCase;

class RequestTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request();
    }

    public function test_get_returns_from_get_then_post(): void
    {
        $_GET['foo'] = 'bar';
        $this->assertSame('bar', $this->request->get('foo'));

        unset($_GET['foo']);
        $_POST['foo'] = 'baz';
        $this->assertSame('baz', $this->request->get('foo'));
    }

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertNull($this->request->get('missing'));
        $this->assertSame('fallback', $this->request->get('missing', 'fallback'));
    }

    public function test_input_delegates_to_get(): void
    {
        $_GET['name'] = 'John';
        $this->assertSame('John', $this->request->input('name'));
    }

    public function test_all_merges_get_and_post(): void
    {
        $_GET = ['a' => 1];
        $_POST = ['b' => 2];
        $all = $this->request->all();
        $this->assertSame(1, $all['a']);
        $this->assertSame(2, $all['b']);
    }

    public function test_only_filters_keys(): void
    {
        $_GET = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = $this->request->only(['a', 'c']);
        $this->assertSame(['a' => 1, 'c' => 3], $result);
    }

    public function test_except_excludes_keys(): void
    {
        $_GET = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = $this->request->except(['b']);
        $this->assertArrayNotHasKey('b', $result);
        $this->assertCount(2, $result);
    }

    public function test_has(): void
    {
        $this->assertFalse($this->request->has('key'));
        $_POST['key'] = 'val';
        $this->assertTrue($this->request->has('key'));
    }

    public function test_method_returns_uppercase(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $this->assertSame('POST', $this->request->method());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertSame('GET', $this->request->method());
    }

    public function test_method_defaults_to_get(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertSame('GET', $this->request->method());
    }

    public function test_path(): void
    {
        $_SERVER['REQUEST_URI'] = '/students/42?tab=info';
        $this->assertSame('/students/42', $this->request->path());
    }

    public function test_path_strips_trailing_slash(): void
    {
        $_SERVER['REQUEST_URI'] = '/students/';
        $this->assertSame('/students', $this->request->path());
    }

    public function test_path_returns_root_for_empty(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertSame('/', $this->request->path());
    }

    public function test_is_secure_detects_https(): void
    {
        $this->assertFalse($this->request->isSecure());

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->request->isSecure());

        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = 443;
        $this->assertTrue($this->request->isSecure());

        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertTrue($this->request->isSecure());
    }

    public function test_url(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/page?q=1';
        $this->assertSame('http://example.com/page?q=1', $this->request->url());

        $_SERVER['HTTPS'] = 'on';
        $this->assertSame('https://example.com/page?q=1', $this->request->url());
    }

    public function test_bearer_token(): void
    {
        $this->assertNull($this->request->bearerToken());

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc123';
        $this->assertSame('abc123', $this->request->bearerToken());

        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic dXNlcjpwYXNz';
        $this->assertNull($this->request->bearerToken());
    }

    public function test_ip_from_x_forwarded_for(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';
        $this->assertSame('10.0.0.1', $this->request->ip());
    }

    public function test_ip_from_x_real_ip(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['HTTP_X_REAL_IP'] = '10.0.0.2';
        $this->assertSame('10.0.0.2', $this->request->ip());
    }

    public function test_ip_from_remote_addr(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertSame('192.168.1.1', $this->request->ip());
    }

    public function test_ip_defaults_to_localhost(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP'], $_SERVER['REMOTE_ADDR']);
        $this->assertSame('127.0.0.1', $this->request->ip());
    }

    public function test_user_agent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $this->assertSame('Mozilla/5.0', $this->request->userAgent());
    }

    public function test_is_json(): void
    {
        $this->assertFalse($this->request->isJson());

        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $this->assertTrue($this->request->isJson());

        $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
        $this->assertTrue($this->request->isJson());
    }

    public function test_content_type(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'text/html';
        $this->assertSame('text/html', $this->request->contentType());
    }
}
