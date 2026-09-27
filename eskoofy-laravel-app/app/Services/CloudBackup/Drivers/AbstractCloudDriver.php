<?php

namespace App\Services\CloudBackup\Drivers;

use App\Services\CloudBackup\Contracts\CloudBackupDriver;
use App\Services\CloudBackup\Contracts\CloudHttpClient;

/**
 * Shared plumbing for the provider drivers: the configured remote folder,
 * file-name sanitising, and JSON decoding that never throws.
 */
abstract class AbstractCloudDriver implements CloudBackupDriver
{
    public function __construct(
        protected CloudHttpClient $http,
        protected array $credentials = [],
        protected ?string $folder = null,
    ) {}

    /**
     * Remote folder for this install. Always a single, sanitised path segment
     * chain — never an absolute path and never a traversal.
     */
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

    /**
     * Remote file names are always `backup_<variant>_<timestamp>.zip` style —
     * a user-supplied name is reduced to a safe basename first.
     */
    public function sanitizeFileName(string $fileName): string
    {
        $base = basename(str_replace('\\', '/', $fileName));
        $base = preg_replace('/[^A-Za-z0-9._-]/', '-', $base) ?? 'backup.zip';

        return $base !== '' ? $base : 'backup.zip';
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function remoteKey(array $result): string
    {
        return (string) ($result['id'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    /**
     * @return array{ok: bool, message: string}
     */
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
