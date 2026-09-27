<?php

namespace App\Services\CloudBackup\Drivers;

/**
 * "This server" provider: mirrors the portable zip into
 * `storage/app/backups/cloud/<folder>/`. Needs no credentials, so it is the
 * safe default on shared hosting and the driver used by the test suite.
 */
class LocalCloudDriver extends AbstractCloudDriver
{
    public function key(): string
    {
        return 'local';
    }

    public function label(): string
    {
        return (string) (config('backup.providers.local.label') ?? 'This server (local folder)');
    }

    public function root(): string
    {
        return rtrim(storage_path('app/backups/cloud'), DIRECTORY_SEPARATOR);
    }

    /**
     * The folder this install mirrors into. Unlike the remote providers this is
     * a real subdirectory, so two installs sharing a `storage/` stay separate.
     */
    public function folderPath(): string
    {
        return $this->root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $this->folder());
    }

    public function test(): array
    {
        $folder = $this->folderPath();
        if (! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) {
            return $this->fail("Local backup folder is not writable: {$folder}");
        }

        return $this->pass("Local folder ready: {$folder}");
    }

    public function upload(string $localPath, string $fileName): array
    {
        if (! is_file($localPath)) {
            return ['error' => 'Backup file not found.'];
        }

        $folder = $this->folderPath();
        if (! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) {
            return ['error' => "Unable to create {$folder}."];
        }

        $target = $this->targetPath($fileName);
        if (! @copy($localPath, $target)) {
            return ['error' => "Unable to write {$target}."];
        }

        return [
            'id' => basename($target),
            'name' => basename($target),
            'size' => (int) @filesize($target),
            'path' => $target,
        ];
    }

    public function download(string $remoteId, string $localPath): ?string
    {
        $source = $this->pathFor($remoteId);
        if ($source === null || ! is_file($source)) {
            return null;
        }

        $dir = dirname($localPath);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        return @copy($source, $localPath) ? $localPath : null;
    }

    public function delete(string $remoteId): bool
    {
        $path = $this->pathFor($remoteId);

        return $path !== null && is_file($path) ? @unlink($path) : false;
    }

    public function list(): array
    {
        $dir = $this->folderPath();
        if (! is_dir($dir)) {
            return [];
        }

        $items = [];
        foreach ((array) glob($dir.'/*.zip') as $path) {
            $items[] = [
                'id' => basename((string) $path),
                'name' => basename((string) $path),
                'size' => (int) @filesize((string) $path),
                'modified' => (int) @filemtime((string) $path),
            ];
        }

        usort($items, static fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $items;
    }

    private function targetPath(string $fileName): string
    {
        return $this->folderPath().DIRECTORY_SEPARATOR.$this->sanitizeFileName($fileName);
    }

    /**
     * Resolve a stored remote key back to a real path inside the backup folder.
     * Anything that escapes the folder resolves to null.
     */
    private function pathFor(string $remoteId): ?string
    {
        $path = $this->folderPath().DIRECTORY_SEPARATOR.$this->sanitizeFileName($remoteId);

        return str_starts_with($path, $this->folderPath().DIRECTORY_SEPARATOR) && is_file($path) ? $path : null;
    }
}
