<?php

namespace App\Services\CloudBackup\Drivers;

/**
 * Google Drive via the Drive v3 REST API.
 *
 * Two credential modes are supported:
 *   - OAuth2 refresh token (client_id + client_secret + refresh_token) — the
 *     access token is minted on demand and reused for the request.
 *   - Service account (service_account_email + service_account_private_key) —
 *     a short-lived RS256 JWT assertion is signed locally and exchanged for an
 *     access token. The private key is NEVER sent to Google (and never used as a
 *     bearer token, which is what a naive implementation does and why it 401s).
 *
 * Uploads are multipart (`files.create` with `uploadType=multipart`); reads use
 * `files.get?alt=media`. Google addresses parents by *id*, so the configured
 * folder name is resolved to a folder id (and created on first use) before any
 * upload or listing.
 */
class GoogleDriveDriver extends AbstractCloudDriver
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const API = 'https://www.googleapis.com/drive/v3/files';

    private const FOLDER_MIME = 'application/vnd.google-apps.folder';

    private const JWT_BEARER_GRANT = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    private ?string $accessToken = null;

    private ?string $folderId = null;

    public function key(): string
    {
        return 'google_drive';
    }

    public function label(): string
    {
        return (string) (config('backup.providers.google_drive.label') ?? 'Google Drive');
    }

    public function test(): array
    {
        $token = $this->token();
        if ($token === null) {
            return $this->fail('Google Drive credentials are incomplete or rejected.');
        }

        $response = $this->http->request('GET', self::API.'?pageSize=1&fields=files(id,name)', [
            'Authorization' => 'Bearer '.$token,
        ]);

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return $this->fail('Google Drive rejected the credentials (HTTP '.$response['status'].').');
        }

        // Also prove the backup folder is usable, not just that the token works.
        if ($this->resolveFolderId() === null) {
            return $this->fail('Google Drive is reachable but the folder "'.$this->folder().'" is not usable.');
        }

        return $this->pass('Connected to Google Drive.');
    }

    public function upload(string $localPath, string $fileName): array
    {
        if (! is_file($localPath)) {
            return ['error' => 'Backup file not found.'];
        }

        $token = $this->token();
        if ($token === null) {
            return ['error' => 'Google Drive credentials are incomplete or rejected.'];
        }

        $folderId = $this->resolveFolderId();
        if ($folderId === null) {
            return ['error' => 'Could not resolve the Google Drive backup folder.'];
        }

        $name = $this->sanitizeFileName($fileName);
        $metadata = [
            'name' => $name,
            'parents' => [$folderId],
        ];

        $boundary = 'eskoofy'.bin2hex(random_bytes(8));
        $body = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .json_encode($metadata, JSON_UNESCAPED_SLASHES)."\r\n"
            ."--{$boundary}\r\n"
            ."Content-Type: application/zip\r\n"
            ."Content-Transfer-Encoding: binary\r\n\r\n"
            .(string) file_get_contents($localPath)."\r\n"
            ."--{$boundary}--";

        $response = $this->http->request(
            'POST',
            self::API.'?uploadType=multipart&fields=id,name,size',
            [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'multipart/related; boundary='.$boundary,
            ],
            $body
        );

        if (($response['error'] ?? null) !== null) {
            return ['error' => 'Google Drive upload failed: '.$response['error']];
        }

        $decoded = $this->decode($response['body']);
        if ($response['status'] >= 400 || empty($decoded['id'])) {
            return ['error' => 'Google Drive upload failed (HTTP '.$response['status'].'): '.($this->errorMessage($decoded) ?: 'unknown error')];
        }

        return [
            'id' => (string) $decoded['id'],
            'name' => (string) ($decoded['name'] ?? $name),
            'size' => (int) ($decoded['size'] ?? filesize($localPath)),
            'path' => $this->folder().'/'.$name,
        ];
    }

    public function download(string $remoteId, string $localPath): ?string
    {
        $token = $this->token();
        if ($token === null) {
            return null;
        }

        $response = $this->http->request('GET', self::API.'/'.rawurlencode($remoteId).'?alt=media', [
            'Authorization' => 'Bearer '.$token,
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

        $response = $this->http->request('DELETE', self::API.'/'.rawurlencode($remoteId), [
            'Authorization' => 'Bearer '.$token,
        ]);

        return in_array($response['status'], [200, 204], true);
    }

    public function list(): array
    {
        $token = $this->token();
        if ($token === null) {
            return [];
        }

        $folderId = $this->resolveFolderId();
        if ($folderId === null) {
            return [];
        }

        $query = http_build_query([
            'q' => "'".$folderId."' in parents and trashed = false",
            'fields' => 'files(id,name,size,modifiedTime)',
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 100,
        ]);

        $response = $this->http->request('GET', self::API.'?'.$query, [
            'Authorization' => 'Bearer '.$token,
        ]);

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return [];
        }

        $items = [];
        foreach ($this->decode($response['body'])['files'] ?? [] as $file) {
            $items[] = [
                'id' => (string) ($file['id'] ?? ''),
                'name' => (string) ($file['name'] ?? ''),
                'size' => (int) ($file['size'] ?? 0),
                'modified' => strtotime((string) ($file['modifiedTime'] ?? '')) ?: 0,
            ];
        }

        return $items;
    }

    // ------------------------------------------------------------------ folder

    /**
     * The Drive **id** of the configured backup folder, creating it on first
     * use. Google addresses parents by id, so uploading with the folder *name*
     * (the obvious implementation) fails with "File not found: …".
     */
    private function resolveFolderId(): ?string
    {
        if ($this->folderId !== null) {
            return $this->folderId;
        }

        $token = $this->token();
        if ($token === null) {
            return null;
        }

        $name = $this->folder();
        $query = http_build_query([
            'q' => "mimeType = '".self::FOLDER_MIME."' and name = '".str_replace("'", "\\'", $name)."' and trashed = false",
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        $response = $this->http->request('GET', self::API.'?'.$query, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $existing = $this->decode($response['body'])['files'][0]['id'] ?? null;
        if (is_string($existing) && $existing !== '') {
            return $this->folderId = $existing;
        }

        $created = $this->http->request(
            'POST',
            self::API.'?fields=id',
            [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            json_encode([
                'name' => $name,
                'mimeType' => self::FOLDER_MIME,
            ], JSON_UNESCAPED_SLASHES)
        );

        $id = $this->decode($created['body'])['id'] ?? null;

        return is_string($id) && $id !== '' ? $this->folderId = $id : null;
    }

    // ------------------------------------------------------------------- token

    /**
     * Mint (or reuse) an access token for this request.
     */
    private function token(): ?string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $email = $this->credential('service_account_email');
        $privateKey = $this->credential('service_account_private_key');
        if ($email !== null && $privateKey !== null) {
            return $this->accessToken = $this->exchange($this->serviceAccountAssertion($email, $privateKey));
        }

        $refreshToken = $this->credential('refresh_token');
        $clientId = $this->credential('client_id');
        if ($refreshToken === null || $clientId === null) {
            return null;
        }

        $fields = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
        ];
        if ($secret = $this->credential('client_secret')) {
            $fields['client_secret'] = $secret;
        }

        return $this->accessToken = $this->exchange($fields);
    }

    /**
     * Build and sign the RS256 JWT assertion that Google's token endpoint
     * accepts instead of a refresh token for service-account mode.
     */
    private function serviceAccountAssertion(string $email, string $privateKey): ?array
    {
        $key = $this->signingKey($privateKey);
        if ($key === null) {
            return null;
        }

        $now = time();
        $claims = [
            'iss' => $email,
            'scope' => $this->scope(),
            'aud' => self::TOKEN_URL,
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            self::base64Url(json_encode($header, JSON_UNESCAPED_SLASHES) ?: ''),
            self::base64Url(json_encode($claims, JSON_UNESCAPED_SLASHES) ?: ''),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return [
            'grant_type' => self::JWT_BEARER_GRANT,
            'assertion' => $signingInput.'.'.self::base64Url($signature),
        ];
    }

    /**
     * Normalise the pasted private key. Admins paste it from the JSON key file,
     * so it arrives with literal "\n" sequences and/or surrounding quotes.
     *
     * @return \OpenSSLAsymmetricKey|array{0: \OpenSSLAsymmetricKey, 1: string}|null
     */
    private function signingKey(string $privateKey)
    {
        $key = str_replace('\\n', "\n", trim($privateKey));
        $key = trim($key, "\"' \t\r\n");

        $resource = openssl_pkey_get_private($key);
        if ($resource === false) {
            return null;
        }

        return $resource;
    }

    private function scope(): string
    {
        $configured = $this->credential('scope');

        return is_string($configured) && $configured !== ''
            ? $configured
            : 'https://www.googleapis.com/auth/drive.file';
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function exchange(array $fields): ?string
    {
        $response = $this->http->request('POST', self::TOKEN_URL, [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], http_build_query($fields));

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return null;
        }

        $token = $this->decode($response['body'])['access_token'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $decoded */
    private function errorMessage(array $decoded): string
    {
        $message = $decoded['error']['message'] ?? $decoded['error_description'] ?? '';

        return is_string($message) ? trim($message) : '';
    }
}
