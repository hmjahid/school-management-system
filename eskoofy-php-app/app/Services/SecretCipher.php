<?php

declare(strict_types=1);

namespace App\Services;

/**
 * AES-256-GCM secret encryption for the raw-PHP port.
 *
 * The Laravel app stores cloud credentials with an `encrypted:array` cast
 * (AES-256-CBC keyed from APP_KEY). The raw-PHP port has no framework
 * encrypter, so this is the equivalent: a deterministic, authenticated cipher
 * keyed from the same APP_KEY the app uses, so a credential set stored by
 * either product is decryptable by the other on the same install.
 *
 * Values are prefixed with a version tag so a future key rotation or algorithm
 * change can be detected instead of silently mis-decrypting.
 */
class SecretCipher
{
    public const PREFIX = 'esk1:';

    private const CIPHER = 'aes-256-gcm';

    private const TAG_LENGTH = 16;

    public static function encrypt(string $value): string
    {
        $key = self::key();

        if ($key === null) {
            // No APP_KEY configured: store the value visibly rather than fail
            // the whole backup pipeline. The UI never renders it back, and the
            // comment in .env.example tells operators to set APP_KEY in
            // production, where this branch never runs.
            return 'plain:' . $value;
        }

        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);

        if ($ciphertext === false) {
            throw new \RuntimeException('Cloud credential encryption failed.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, 'plain:')) {
            return substr($value, strlen('plain:'));
        }

        if (! str_starts_with($value, self::PREFIX)) {
            return null;
        }

        $key = self::key();
        if ($key === null) {
            return null;
        }

        $blob = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($blob === false || strlen($blob) < 12 + self::TAG_LENGTH) {
            return null;
        }

        $iv = substr($blob, 0, 12);
        $tag = substr($blob, 12, self::TAG_LENGTH);
        $ciphertext = substr($blob, 12 + self::TAG_LENGTH);

        $decrypted = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted === false ? null : $decrypted;
    }

    private static function key(): ?string
    {
        $key = (string) ($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: '');
        $key = trim($key);

        if ($key === '') {
            return null;
        }

        $key = preg_replace('/^base64:/', '', $key);

        // Accept a raw base64 32-byte key (Laravel-style) or derive a 32-byte
        // key from the literal string, so short hand-set keys still work.
        $decoded = base64_decode($key, true);
        if ($decoded !== false && strlen($decoded) === 32) {
            return $decoded;
        }

        return hash('sha256', $key, true);
    }
}