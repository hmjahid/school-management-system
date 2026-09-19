<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Middleware\ThrottleMiddleware;
use PHPUnit\Framework\TestCase;

class LicenseApiSecurityTest extends TestCase
{
    public function test_domain_validation(): void
    {
        $controller = new \App\Controllers\Api\LicenseApiController();
        $method = new \ReflectionMethod($controller, 'validDomain');
        $method->setAccessible(true);

        foreach (['school.com', 'sub.school.example.org', 'localhost'] as $valid) {
            $this->assertTrue($method->invoke($controller, $valid), "'{$valid}' should be valid");
        }
        foreach (['bad..domain', 'http://evil.com', 'js%00.com', ''] as $invalid) {
            $this->assertFalse($method->invoke($controller, $invalid), "'{$invalid}' should be rejected");
        }
    }

    public function test_machine_id_is_bounded_and_printable(): void
    {
        $controller = new \App\Controllers\Api\LicenseApiController();
        $method = new \ReflectionMethod($controller, 'normalizeMachine');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($controller, null));
        $this->assertNull($method->invoke($controller, ''));
        $long = str_repeat('A', 500);
        $this->assertSame(191, strlen($method->invoke($controller, $long)));
        $this->assertSame('abc-123evil', $method->invoke($controller, "abc-123\x00evil"));
    }

    public function test_throttle_parses_parameterized_name(): void
    {
        $ref = new \ReflectionClass(ThrottleMiddleware::class);
        $instance = $ref->newInstance('5,2');
        $max = $ref->getProperty('maxAttempts');
        $max->setAccessible(true);
        $decay = $ref->getProperty('decayMinutes');
        $decay->setAccessible(true);

        $this->assertSame(5, $max->getValue($instance));
        $this->assertSame(2, $decay->getValue($instance));
    }
}