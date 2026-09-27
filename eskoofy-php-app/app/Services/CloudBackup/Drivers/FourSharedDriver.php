<?php

declare(strict_types=1);

namespace App\Services\CloudBackup\Drivers;

/**
 * 4shared via its REST API.
 *
 * Credentials: api_key (plus username/password for the authenticated variant).
 * Uploads are multipart POSTs to `https://api.4shared.com/v1/upload`; listing
 * and deletion use the account's file endpoints. Every call carries the API
 * key in the `X-API-KEY` header and HTTP Basic auth when a username is set.
 */
class FourSharedDriver extends AbstractCloudDriver
{
    private const API = 'https://api.4shared.com/v1';

    public function key(): string
    {
        return '4shared';
    }

    public function label(): string
    {
        return (string) (config('backup.providers.4shared.label') ?? '4shared');
    }

    public function test(): array
    {
        if ($this->credential('api_key') === null) {
            return $this->fail('4shared API key is missing.');
        }

        $response = $this->http->request('GET', self::API.'/account/info', $this->headers());

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return $this->fail('4shared rejected the credentials (HTTP '.$response['status'].').');
        }

        return $this->pass('Connected to 4shared.');
    }

    public function upload(string $localPath, string $fileName): array
    {
        if (! is_file($localPath)) {
            return ['error' => 'Backup file not found.'];
        }

        $name = $this->sanitizeFileName($fileName);
        $response = $this->http->request('POST', self::API.'/upload', $this->headers(), [
            'file' => [
                'path' => $localPath,
                'filename' => $name,
                'mime' => 'application/zip',
            ],
            'folder' => $this->folder(),
        ]);

        if (($response['error'] ?? null) !== null) {
            return ['error' => '4shared upload failed: '.$response['error']];
        }

        $decoded = $this->decode($response['body']);
        $id = (string) ($decoded['id'] ?? $decoded['file_id'] ?? $decoded['link'] ?? '');
        if ($response['status'] >= 400 || $id === '') {
            return ['error' => '4shared upload failed (HTTP '.$response['status'].'): '.($decoded['error'] ?? 'unknown error')];
        }

        return [
            'id' => $id,
            'name' => (string) ($decoded['filename'] ?? $name),
            'size' => (int) ($decoded['size'] ?? filesize($localPath)),
            'path' => $this->folder().'/'.$name,
        ];
    }

    public function download(string $remoteId, string $localPath): ?string
    {
        $response = $this->http->request('GET', self::API.'/download/'.rawurlencode($remoteId), $this->headers());

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return null;
        }

        $dir = dirname($localPath);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        return @file_put_contents($localPath, $response['body']) !== false ? $localPath : null;
    }

    public function delete(string $remoteId): bool
    {
        $response = $this->http->request('DELETE', self::API.'/files/'.rawurlencode($remoteId), $this->headers());

        return ($response['error'] ?? null) === null && $response['status'] < 400;
    }

    public function list(): array
    {
        $response = $this->http->request('GET', self::API.'/files?folder='.rawurlencode($this->folder()), $this->headers());

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return [];
        }

        $entries = $this->decode($response['body']);
        $entries = $entries['files'] ?? $entries['items'] ?? $entries;

        $items = [];
        foreach (is_array($entries) ? $entries : [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $items[] = [
                'id' => (string) ($entry['id'] ?? $entry['file_id'] ?? $entry['link'] ?? ''),
                'name' => (string) ($entry['filename'] ?? $entry['name'] ?? ''),
                'size' => (int) ($entry['size'] ?? 0),
                'modified' => strtotime((string) ($entry['created'] ?? $entry['modified'] ?? '')) ?: 0,
            ];
        }

        usort($items, static fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $items;
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function headers(array $extra = []): array
    {
        $headers = $extra;
        if ($key = $this->credential('api_key')) {
            $headers['X-API-KEY'] = $key;
        }
        if (($username = $this->credential('username')) !== null) {
            $headers['Authorization'] = 'Basic '.base64_encode($username.':'.(string) $this->credential('password'));
        }

        return $headers;
    }
}
