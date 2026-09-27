<?php

declare(strict_types=1);

namespace App\Services\CloudBackup\Drivers;

/**
 * Dropbox via the v2 HTTP API.
 *
 * Credentials: app_key + app_secret + refresh_token (long-lived) or a plain
 * access_token. Uploads use `content.dropboxapi.com/2/files/upload` with the
 * `Dropbox-API-Arg` header; reads use `content.dropboxapi.com/2/files/download`;
 * metadata (used by list()) comes from `api.dropboxapi.com/2/files/list_folder`.
 *
 * `files/download`, `files/delete_v2` and `files/list_folder` all take a *path*,
 * never a file id, so the path is what this driver stores as the remote key —
 * see remoteKey() below. The native id is still returned for display.
 */
class DropboxDriver extends AbstractCloudDriver
{
    private const TOKEN_URL = 'https://api.dropboxapi.com/oauth2/token';

    private const UPLOAD_URL = 'https://content.dropboxapi.com/2/files/upload';

    private const DOWNLOAD_URL = 'https://content.dropboxapi.com/2/files/download';

    private const LIST_URL = 'https://api.dropboxapi.com/2/files/list_folder';

    private const DELETE_URL = 'https://api.dropboxapi.com/2/files/delete_v2';

    private ?string $accessToken = null;

    public function key(): string
    {
        return 'dropbox';
    }

    public function label(): string
    {
        return (string) (config('backup.providers.dropbox.label') ?? 'Dropbox');
    }

    public function test(): array
    {
        $token = $this->token();
        if ($token === null) {
            return $this->fail('Dropbox credentials are incomplete or rejected.');
        }

        $response = $this->http->request('POST', self::LIST_URL, [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ], json_encode(['path' => '', 'limit' => 1]));

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return $this->fail('Dropbox rejected the credentials (HTTP '.$response['status'].').');
        }

        return $this->pass('Connected to Dropbox.');
    }

    public function upload(string $localPath, string $fileName): array
    {
        if (! is_file($localPath)) {
            return ['error' => 'Backup file not found.'];
        }

        $token = $this->token();
        if ($token === null) {
            return ['error' => 'Dropbox credentials are incomplete or rejected.'];
        }

        $name = $this->sanitizeFileName($fileName);
        $response = $this->http->request('POST', self::UPLOAD_URL, [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/octet-stream',
            'Dropbox-API-Arg' => json_encode([
                'path' => '/'.$this->folder().'/'.$name,
                'mode' => 'overwrite',
                'autorename' => false,
                'mute' => true,
            ], JSON_UNESCAPED_SLASHES),
        ], (string) file_get_contents($localPath));

        if (($response['error'] ?? null) !== null) {
            return ['error' => 'Dropbox upload failed: '.$response['error']];
        }

        $decoded = $this->decode($response['body']);
        if ($response['status'] >= 400 || empty($decoded['id'])) {
            return ['error' => 'Dropbox upload failed (HTTP '.$response['status'].'): '.($this->errorSummary($decoded) ?: 'unknown error')];
        }

        return [
            'id' => (string) $decoded['id'],
            'name' => (string) ($decoded['name'] ?? $name),
            'size' => (int) ($decoded['size'] ?? filesize($localPath)),
            'path' => (string) ($decoded['path_display'] ?? '/'.$this->folder().'/'.$name),
        ];
    }

    /**
     * Dropbox only addresses files by path, so the stored remote key is the
     * path (e.g. "/eskoofy-backups/backup_bd_20260927.zip"). A bare Dropbox id
     * ("id:AbC…") is accepted as well so the value is used verbatim.
     */
    public function remoteKey(array $result): string
    {
        $path = trim((string) ($result['path'] ?? ''));
        if ($path !== '') {
            return $path;
        }

        // list_folder entries only guarantee `path_lower`; fall back to it
        // before the native id, which this driver cannot download by.
        $pathLower = trim((string) ($result['path_lower'] ?? ''));

        return $pathLower !== '' ? $pathLower : (string) ($result['id'] ?? '');
    }

    public function download(string $remoteId, string $localPath): ?string
    {
        $token = $this->token();
        if ($token === null) {
            return null;
        }

        $response = $this->http->request('POST', self::DOWNLOAD_URL, [
            'Authorization' => 'Bearer '.$token,
            'Dropbox-API-Arg' => json_encode(['path' => $remoteId]),
        ]);

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
        $token = $this->token();
        if ($token === null) {
            return false;
        }

        $response = $this->http->request('POST', self::DELETE_URL, [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ], json_encode(['path' => $remoteId]));

        return ($response['error'] ?? null) === null && $response['status'] === 200;
    }

    public function list(): array
    {
        $token = $this->token();
        if ($token === null) {
            return [];
        }

        $response = $this->http->request('POST', self::LIST_URL, [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ], json_encode(['path' => '/'.$this->folder(), 'limit' => 200]));

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return [];
        }

        $items = [];
        foreach ($this->decode($response['body'])['entries'] ?? [] as $entry) {
            if (($entry['.tag'] ?? 'file') !== 'file') {
                continue;
            }
            $item = [
                'id' => (string) ($entry['path_display'] ?? $entry['path_lower'] ?? $entry['id'] ?? ''),
                'dropbox_id' => (string) ($entry['id'] ?? ''),
                'path' => (string) ($entry['path_display'] ?? $entry['path_lower'] ?? ''),
                'name' => (string) ($entry['name'] ?? ''),
                'size' => (int) ($entry['size'] ?? 0),
                'modified' => strtotime((string) ($entry['server_modified'] ?? '')) ?: 0,
            ];
            $item['id'] = $this->remoteKey($item);
            $items[] = $item;
        }

        usort($items, static fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $items;
    }

    private function token(): ?string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $refreshToken = $this->credential('refresh_token');
        $appKey = $this->credential('app_key');
        if ($refreshToken === null || $appKey === null) {
            return $this->accessToken = $this->credential('access_token');
        }

        $fields = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];
        if ($secret = $this->credential('app_secret')) {
            $fields['client_id'] = $appKey;
            $fields['client_secret'] = $secret;
        }

        $response = $this->http->request('POST', self::TOKEN_URL, [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], http_build_query($fields));

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return null;
        }

        $token = $this->decode($response['body'])['access_token'] ?? null;

        return is_string($token) && $token !== '' ? $this->accessToken = $token : null;
    }

    private function errorSummary(array $decoded): string
    {
        $summary = $decoded['error_summary'] ?? '';

        return is_string($summary) ? trim($summary) : '';
    }
}
