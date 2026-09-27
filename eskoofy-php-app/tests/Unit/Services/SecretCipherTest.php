<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SecretCipher;
use Tests\TestCase;

class SecretCipherTest extends TestCase
{
    public function test_round_trip(): void
    {
        $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));

        $encrypted = SecretCipher::encrypt('{"app_secret":"s3cr3t"}');
        $this->assertStringStartsWith('esk1:', $encrypted);
        $this->assertNotSame('{"app_secret":"s3cr3t"}', $encrypted);

        $this->assertSame('{"app_secret":"s3cr3t"}', SecretCipher::decrypt($encrypted));
    }

    public function test_different_key_does_not_decrypt(): void
    {
        $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));
        $encrypted = SecretCipher::encrypt('secret');

        $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('b', 32));
        $this->assertNull(SecretCipher::decrypt($encrypted));
    }

    public function test_plain_fallback_without_key(): void
    {
        unset($_ENV['APP_KEY']);
        putenv('APP_KEY');

        $this->assertStringStartsWith('plain:', SecretCipher::encrypt('value'));
        $this->assertSame('value', SecretCipher::decrypt('plain:value'));
    }

    public function test_short_literal_key_is_hashed(): void
    {
        $_ENV['APP_KEY'] = 'a-short-literal-key';

        $encrypted = SecretCipher::encrypt('secret');
        $this->assertSame('secret', SecretCipher::decrypt($encrypted));
    }
}