<?php

declare(strict_types=1);

namespace App\Services\CloudBackup\Contracts;

/**
 * Injectable HTTP transport so driver tests never touch the network.
 * Implementations must be HTTPS-only and TLS-verifying.
 */
interface CloudHttpClient
{
    /**
     * @param  array<string, string>  $headers
     * @param  string|array<string, mixed>|null  $body  raw body, or a multipart
     *                                                  field map. A multipart file part is `['path' => string, 'filename' => string,
     *                                                  'mime' => string]`; every other value is sent verbatim.
     * @return array{status: int, body: string, headers: array<string, string>, error: string|null}
     */
    public function request(string $method, string $url, array $headers = [], string|array|null $body = null): array;
}