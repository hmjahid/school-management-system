<?php

declare(strict_types=1);

namespace App\Services\CloudBackup\Drivers;

use App\Services\CloudBackup\Contracts\CloudBackupDriver;
use App\Services\CloudBackup\Contracts\CloudHttpClient;

/**
 * Shared plumbing for the provider drivers: the configured remote folder,
 * file-name sanitising, and JSON decoding that never throws.
 * Port of the Laravel app's AbstractCloudDriver.
 */
abstract class AbstractCloudDriver implements CloudBackupDriver
{
    public function __construct(
        protected CloudHttpClient $http,
        protected array $credentials = [],
        protected ?string $folder = null,
    ) {}

    public function folder(): string
    {
        $folder = trim((string) ($this->folder ?: config('backup.folder', 'eskoofy-backups')));
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $folder)) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = preg_replace('/[^A-Za-z0-9._-]/', '-', $segment);
        }

        return implode('/', $segments);
    }

    public function sanitizeFileName(string $fileName): string
    {
        $base = basename(str_replace('\\', '/', $fileName));
        $base = preg_replace('/[^A-Za-z0-9._-]/', '-', $base) ?? 'backup.zip';

        return $base !== '' ? $base : 'backup.zip';
    }

    public function remoteKey(array $result): string
    {
        return (string) ($result['id'] ?? '');
    }

    protected function decode(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array{ok: bool, message: string} */
    protected function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    /** @return array{ok: bool, message: string} */
    protected function pass(string $message): array
    {
        return ['ok' => true, 'message' => $message];
    }

    protected function credential(string $key): ?string
    {
        $value = $this->credentials[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}