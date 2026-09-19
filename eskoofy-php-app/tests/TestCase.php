<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_X_REAL_IP'],
            $_SERVER['HTTP_X_FORWARDED_PROTO'],
            $_SERVER['HTTP_X_CSRF_TOKEN'],
            $_SERVER['HTTP_ACCEPT'],
            $_SERVER['CONTENT_TYPE'],
            $_SERVER['HTTPS'],
            $_SERVER['SERVER_PORT']
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    protected function withoutExit(): callable
    {
        return function (callable $callback) {
            ob_start();
            try {
                $callback();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            ob_end_clean();
        };
    }
}
