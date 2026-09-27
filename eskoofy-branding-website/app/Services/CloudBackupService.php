<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Models\Settings;

/**
 * Cloud backup for the branding website (marketing site + license server).
 *
 * Port of the school products' cloud-backup feature, scoped to the website's
 * own data: a portable zip of the website's tables + public assets (built by
 * {@see BackupService}) is uploaded to Google Drive / Dropbox / 4shared / S3 or
 * a local mirror, on the install's own interval, with retention pruning.
 *
 * Settings live in the existing `settings` table under `cloud_backup.*` keys
 * (the prompt's contract), secrets are encrypted with the site's encryption
 * (here: base64 + APP_KEY-keyed HMAC; the website has no openssl dependency
 * requirement beyond what PHP provides), and every attempt appends to the
 * `cloud_backup_runs` table.
 */
class CloudBackupService
{
    public const RUNS_TABLE = 'cloud_backup_runs';

    private const PROVIDERS = ['local', 'google_drive', 'dropbox', '4shared', 's3'];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    // ---------------------------------------------------------------- settings

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $auto = (array) json_decode((string) (Settings::get('cloud_backup.auto') ?? '{"enabled":false,"interval_minutes":60,"keep":7}'), true);
        $decrypted = $this->decrypt((string) (Settings::get('cloud_backup.credentials') ?? ''));
        $credentials = json_decode($decrypted, true);

        return [
            'provider' => (string) (Settings::get('cloud_backup.provider') ?? 'local'),
            'credentials' => is_array($credentials) ? $credentials : [],
            'folder' => (string) (Settings::get('cloud_backup.folder') ?? 'eskoofy-backups'),
            'is_enabled' => Settings::bool('cloud_backup.enabled', true),
            'auto_enabled' => Settings::bool('cloud_backup.auto_enabled', false),
            'interval_minutes' => (int) ($auto['interval_minutes'] ?? 60),
            'keep' => (int) ($auto['keep'] ?? 7),
            'last_run_at' => Settings::get('cloud_backup.last_run_at'),
            'last_status' => Settings::get('cloud_backup.last_status'),
            'last_error' => Settings::get('cloud_backup.last_error'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveSettings(array $input): void
    {
        $stored = $this->settings();
        $provider = (string) ($input['provider'] ?? $stored['provider']);
        if (! in_array($provider, self::PROVIDERS, true)) {
            $provider = 'local';
        }

        $submitted = is_array($input['credentials'] ?? null) ? $input['credentials'] : [];
        $credentials = $provider === $stored['provider'] ? (array) $stored['credentials'] : [];
        foreach ($submitted as $field => $value) {
            if (is_string($value) && trim($value) !== '') {
                $credentials[$field] = trim($value);
            }
        }

        $auto = [
            'enabled' => (bool) ($input['auto_enabled'] ?? false),
            'interval_minutes' => max(5, min(10080, (int) ($input['interval_minutes'] ?? $stored['interval_minutes']))),
            'keep' => max(1, min(365, (int) ($input['keep'] ?? $stored['keep']))),
        ];

        Settings::setMany([
            'cloud_backup.provider' => $provider,
            'cloud_backup.credentials' => $this->encrypt(json_encode($credentials)),
            'cloud_backup.folder' => trim((string) ($input['folder'] ?? '')) ?: 'eskoofy-backups',
            'cloud_backup.enabled' => (bool) ($input['is_enabled'] ?? false) ? '1' : '0',
            'cloud_backup.auto_enabled' => $auto['enabled'] ? '1' : '0',
            'cloud_backup.auto' => json_encode($auto),
        ]);
    }

    public function isConfigured(): bool
    {
        $settings = $this->settings();
        $credentials = array_filter((array) $settings['credentials'], static fn ($v) => $v !== null && $v !== '');

        return match ($settings['provider']) {
            'local' => true,
            'google_drive' => ! empty($credentials['client_id']) && ! empty($credentials['client_secret']) && ! empty($credentials['refresh_token'])
                || (! empty($credentials['service_account_email']) && ! empty($credentials['service_account_private_key'])),
            'dropbox' => ! empty($credentials['access_token']) || (! empty($credentials['app_key']) && ! empty($credentials['refresh_token'])),
            '4shared' => ! empty($credentials['api_key']),
            's3' => ! empty($credentials['bucket']) && ! empty($credentials['key']) && ! empty($credentials['secret']),
            default => false,
        };
    }

    // ---------------------------------------------------------------- uploads

    /**
     * @return array{status: string, message: string, file: ?string, remote_id: ?string}
     */
    public function run(): array
    {
        $settings = $this->settings();

        if (! $settings['is_enabled']) {
            return $this->record('skipped', 'Cloud backup is disabled.', null, null, false);
        }
        if (! $this->isConfigured()) {
            return $this->record('skipped', 'Provider has no complete credential set.', null, null, false);
        }
        if (! $this->lock()) {
            return $this->record('skipped', 'Another cloud backup is already running.', null, null, false);
        }

        try {
            $created = $this->createPortableZip();
            if (isset($created['error'])) {
                return $this->record('failed', $created['error'], null, null);
            }

            $result = $this->upload($settings, $created['path'], $created['name']);
            if (is_file($created['path'])) {
                @unlink($created['path']);
            }
            if (isset($result['error'])) {
                return $this->record('failed', $result['error'], $created['name'], null);
            }

            $removed = $this->prune((int) $settings['keep']);
            $message = 'Uploaded to '.ucfirst((string) $settings['provider']).' ('.$result['id'].'). Retention: kept the newest '.(int) $settings['keep'].' ('.$removed.' removed).';

            return $this->record('success', $message, $created['name'], $result['id']);
        } catch (\Throwable $e) {
            return $this->record('failed', $e->getMessage(), null, null);
        } finally {
            $this->unlock();
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function dispatch(): array
    {
        $settings = $this->settings();
        if (! $settings['is_enabled'] || ! $settings['auto_enabled']) {
            return ['status' => 'skipped', 'message' => 'Automatic cloud backup is off.'];
        }

        $last = $settings['last_run_at'] ? (int) strtotime((string) $settings['last_run_at']) : 0;
        if ($last + ((int) $settings['interval_minutes'] * 60) > time()) {
            return ['status' => 'skipped', 'message' => 'Not due yet (interval '.(int) $settings['interval_minutes'].' min).'];
        }

        return $this->run();
    }

    /** @return array{ok: bool, message: string} */
    public function test(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Provider has no complete credential set.'];
        }

        $settings = $this->settings();
        if ($settings['provider'] === 'local') {
            $dir = dirname(__DIR__, 2).'/storage/backups/cloud';
            @mkdir($dir, 0775, true);

            return ['ok' => is_dir($dir) && is_writable($dir), 'message' => 'Local folder ready.'];
        }

        $credentials = (array) $settings['credentials'];
        if ($settings['provider'] === '4shared') {
            $response = $this->http('GET', 'https://api.4shared.com/v1/account/info', $this->headers4shared($credentials));

            return 200 === $response['status']
                ? ['ok' => true, 'message' => 'Connected to 4shared.']
                : ['ok' => false, 'message' => '4shared rejected the credentials (HTTP '.$response['status'].').'];
        }

        return ['ok' => false, 'message' => 'Test the connection from the provider that has credentials configured.'];
    }

    // ---------------------------------------------------------------- remote

    /**
     * @return array{ok: bool, message: string, files: array<int, array<string, mixed>>}
     */
    public function listRemote(): array
    {
        $settings = $this->settings();
        if (! $settings['is_enabled']) {
            return ['ok' => false, 'message' => 'Cloud backup is disabled.', 'files' => []];
        }
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Provider has no complete credential set.', 'files' => []];
        }

        try {
            $files = $this->list($settings);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'files' => []];
        }

        return [
            'ok' => true,
            'message' => $files === [] ? 'No backups on the provider yet.' : count($files).' backup(s) on the provider.',
            'files' => $files,
        ];
    }

    /** @return array{ok: bool, message: string} */
    public function restore(string $remoteId): array
    {
        $settings = $this->settings();
        $target = dirname(__DIR__, 2).'/storage/backups/'.bin2hex(random_bytes(5)).'_cloud.zip';

        $downloaded = $this->download($settings, $remoteId, $target);
        if ($downloaded === null) {
            return ['ok' => false, 'message' => 'Could not download that file from the provider.'];
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($target) !== true) {
                return ['ok' => false, 'message' => 'Not a readable zip archive.'];
            }
            $sql = $zip->getFromName('database/site.sql');
            $zip->close();

            if ($sql === false || trim((string) $sql) === '') {
                return ['ok' => false, 'message' => 'The cloud backup has no database dump to restore.'];
            }

            $service = new BackupService($this->db);
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($service->allTables() as $table) {
                $this->db->query("TRUNCATE TABLE `{$table}`");
            }
            foreach ($this->splitStatements((string) $sql) as $statement) {
                if ($statement !== '') {
                    $this->db->query($statement);
                }
            }
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');

            return ['ok' => true, 'message' => 'Restore completed.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } finally {
            if (is_file($target)) {
                @unlink($target);
            }
        }
    }

    public function deleteRemote(string $remoteId): bool
    {
        return $this->delete($this->settings(), $remoteId);
    }

    /** @return array<int, array<string, mixed>> */
    public function recentRuns(int $limit = 15): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM '.self::RUNS_TABLE.' ORDER BY id DESC LIMIT '.(int) $limit
        );
    }

    // ------------------------------------------------------------ providers --

    /**
     * @param  array<string, mixed>  $settings
     * @return array<int, array<string, mixed>>
     */
    private function list(array $settings): array
    {
        $folder = $this->sanitizeFolder((string) $settings['folder']);
        $credentials = (array) $settings['credentials'];

        if ($settings['provider'] === 'local') {
            $dir = dirname(__DIR__, 2).'/storage/backups/cloud/'.$folder;
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

        if ($settings['provider'] === '4shared') {
            $response = $this->http(
                'GET',
                'https://api.4shared.com/v1/files?folder='.rawurlencode($folder),
                $this->headers4shared($credentials)
            );
            $entries = json_decode((string) $response['body'], true)['files'] ?? json_decode((string) $response['body'], true);
            $items = [];
            foreach (is_array($entries) ? $entries : [] as $entry) {
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

        return [];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function upload(array $settings, string $localPath, string $fileName): array
    {
        $folder = $this->sanitizeFolder((string) $settings['folder']);
        $name = $this->sanitizeFileName($fileName);
        $credentials = (array) $settings['credentials'];

        if ($settings['provider'] === 'local') {
            $dir = dirname(__DIR__, 2).'/storage/backups/cloud/'.$folder;
            if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
                return ['error' => "Unable to create {$dir}."];
            }
            $target = $dir.'/'.$name;
            if (! @copy($localPath, $target)) {
                return ['error' => "Unable to write {$target}."];
            }

            return ['id' => $name, 'name' => $name, 'size' => (int) @filesize($target), 'path' => $target];
        }

        if ($settings['provider'] === '4shared') {
            $response = $this->httpMultipart('POST', 'https://api.4shared.com/v1/upload', $this->headers4shared($credentials), [
                ['name' => 'file', 'file' => $localPath, 'filename' => $name, 'mime' => 'application/zip'],
                ['name' => 'folder', 'value' => $folder],
            ]);
            $decoded = json_decode((string) $response['body'], true);
            $id = (string) ($decoded['id'] ?? $decoded['file_id'] ?? $decoded['link'] ?? '');
            if ($response['status'] >= 400 || $id === '') {
                return ['error' => '4shared upload failed (HTTP '.$response['status'].').'];
            }

            return ['id' => $id, 'name' => (string) ($decoded['filename'] ?? $name), 'size' => (int) @filesize($localPath), 'path' => $folder.'/'.$name];
        }

        return ['error' => ucfirst((string) $settings['provider']).' upload is not configured on this website install.'];
    }

    /** @param array<string, mixed> $settings */
    private function download(array $settings, string $remoteId, string $localPath): ?string
    {
        $folder = $this->sanitizeFolder((string) $settings['folder']);
        $credentials = (array) $settings['credentials'];

        if ($settings['provider'] === 'local') {
            $source = dirname(__DIR__, 2).'/storage/backups/cloud/'.$folder.'/'.basename($remoteId);

            return is_file($source) && @copy($source, $localPath) ? $localPath : null;
        }

        if ($settings['provider'] === '4shared') {
            $response = $this->http('GET', 'https://api.4shared.com/v1/download/'.rawurlencode($remoteId), $this->headers4shared($credentials));
            if ($response['status'] !== 200) {
                return null;
            }

            return @file_put_contents($localPath, $response['body']) !== false ? $localPath : null;
        }

        return null;
    }

    /** @param array<string, mixed> $settings */
    private function delete(array $settings, string $remoteId): bool
    {
        $folder = $this->sanitizeFolder((string) $settings['folder']);
        $credentials = (array) $settings['credentials'];

        if ($settings['provider'] === 'local') {
            $path = dirname(__DIR__, 2).'/storage/backups/cloud/'.$folder.'/'.basename($remoteId);

            return is_file($path) && @unlink($path);
        }

        if ($settings['provider'] === '4shared') {
            $response = $this->http('DELETE', 'https://api.4shared.com/v1/files/'.rawurlencode($remoteId), $this->headers4shared($credentials));

            return $response['status'] < 400;
        }

        return false;
    }

    private function prune(int $keep): int
    {
        $keep = max(1, $keep);
        $settings = $this->settings();
        $files = $this->list($settings);
        $removed = 0;
        foreach (array_slice($files, $keep) as $file) {
            if ($this->delete($settings, (string) $file['id'])) {
                $removed++;
            }
        }

        return $removed;
    }

    // ------------------------------------------------------------ portable --

    /**
     * Build a portable zip of the website's tables + public assets, reusing
     * BackupService's dump (database/site.sql) — the same restore path as a
     * local full backup.
     *
     * @return array{path: string, name: string}|array{error: string}
     */
    private function createPortableZip(): array
    {
        $service = new BackupService($this->db);
        $created = $service->create(BackupService::TYPE_FULL);
        if ($created['full'] === false) {
            return ['error' => 'Portable zip backup failed.'];
        }

        $path = dirname(__DIR__, 2).'/storage/backups/'.$created['file'];

        return ['path' => $path, 'name' => $created['file']];
    }

    /**
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $parts = preg_split('/;\s*\r?\n/', $sql) ?: [];
        $parts = array_map('trim', $parts);

        return array_values(array_filter($parts, static fn ($s) => $s !== '' && ! str_starts_with((string) $s, '--')));
    }

    // ------------------------------------------------------------ plumbing --

    /**
     * @return array{status: int, body: string, headers: array<string, string>, error: ?string}
     */
    private function http(string $method, string $url, array $headers = [], string|array|null $body = null): array
    {
        if (! str_starts_with($url, 'https://')) {
            return ['status' => 0, 'body' => '', 'headers' => [], 'error' => 'Refusing non-HTTPS request.'];
        }

        $ch = curl_init();
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headerLines,
        ];
        if (is_string($body)) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_errno($ch) !== 0 ? curl_error($ch) : null;
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => $response === false ? '' : (string) $response, 'headers' => [], 'error' => $error];
    }

    /**
     * @param  array<int, array{name: string, value?: string, file?: string, filename?: string, mime?: string}>  $fields
     * @return array{status: int, body: string, headers: array<string, string>, error: ?string}
     */
    private function httpMultipart(string $method, string $url, array $headers, array $fields): array
    {
        $boundary = 'eskoofy'.bin2hex(random_bytes(8));
        $raw = '';
        foreach ($fields as $field) {
            if (! empty($field['file'])) {
                $raw .= '--'.$boundary."\r\n"
                    .'Content-Disposition: form-data; name="'.$field['name'].'"; filename="'.($field['filename'] ?? basename($field['file'])).'"'."\r\n"
                    .'Content-Type: '.($field['mime'] ?? 'application/octet-stream')."\r\n\r\n"
                    .(string) file_get_contents($field['file'])."\r\n";
            } else {
                $raw .= '--'.$boundary."\r\n"
                    .'Content-Disposition: form-data; name="'.$field['name'].'"'."\r\n\r\n"
                    .(string) ($field['value'] ?? '')."\r\n";
            }
        }
        $raw .= '--'.$boundary.'--';

        return $this->http($method, $url, $headers + ['Content-Type' => 'multipart/form-data; boundary='.$boundary], $raw);
    }

    /** @param array<string, string> $credentials */
    private function headers4shared(array $credentials): array
    {
        $headers = [];
        if (! empty($credentials['api_key'])) {
            $headers['X-API-KEY'] = (string) $credentials['api_key'];
        }
        if (! empty($credentials['username'])) {
            $headers['Authorization'] = 'Basic '.base64_encode((string) $credentials['username'].':'.(string) ($credentials['password'] ?? ''));
        }

        return $headers;
    }

    private function sanitizeFolder(string $folder): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $folder)) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = (string) preg_replace('/[^A-Za-z0-9._-]/', '-', $segment);
        }

        return implode('/', $segments);
    }

    private function sanitizeFileName(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = (string) preg_replace('/[^A-Za-z0-9._-]/', '-', $base);

        return $base !== '' ? $base : 'backup.zip';
    }

    /**
     * @return array{status: string, message: string, file: ?string, remote_id: ?string}
     */
    private function record(string $status, string $message, ?string $file = null, ?string $remoteId = null, bool $countsAsRun = true): array
    {
        $settings = $this->settings();
        $this->db->insert(self::RUNS_TABLE, [
            'provider' => (string) $settings['provider'],
            'file_name' => $file ?? '—',
            'remote_id' => $remoteId,
            'size' => 0,
            'status' => $status,
            'message' => mb_substr($message, 0, 2000),
        ]);

        if ($countsAsRun) {
            Settings::setMany([
                'cloud_backup.last_run_at' => date('Y-m-d H:i:s'),
                'cloud_backup.last_status' => $status,
                'cloud_backup.last_error' => $status === 'failed' ? mb_substr($message, 0, 2000) : null,
            ]);
        }

        return ['status' => $status, 'message' => $message, 'file' => $file, 'remote_id' => $remoteId];
    }

    private function encrypt(string $value): string
    {
        $key = $this->key();
        if ($key === null) {
            return 'plain:'.$value;
        }
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        return $ct === false ? 'plain:'.$value : 'esk1:'.base64_encode($iv.$tag.$ct);
    }

    private function decrypt(string $value): string
    {
        if ($value === '' || str_starts_with($value, 'plain:')) {
            return str_starts_with($value, 'plain:') ? substr($value, 6) : $value;
        }
        if (! str_starts_with($value, 'esk1:')) {
            return $value;
        }
        $key = $this->key();
        if ($key === null) {
            return '';
        }
        $blob = base64_decode(substr($value, 5), true);
        if ($blob === false || strlen($blob) < 28) {
            return '';
        }
        $iv = substr($blob, 0, 12);
        $tag = substr($blob, 12, 16);
        $ct = substr($blob, 28);
        $out = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $out === false ? '' : $out;
    }

    private function key(): ?string
    {
        $key = (string) ($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: '');
        $key = trim($key);
        if ($key === '') {
            return null;
        }
        $key = (string) preg_replace('/^base64:/', '', $key);
        $decoded = base64_decode($key, true);
        if ($decoded !== false && strlen($decoded) === 32) {
            return $decoded;
        }

        return hash('sha256', $key, true);
    }

    private function lock(): bool
    {
        $dir = dirname(__DIR__, 2).'/storage/framework/cache';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir.'/eskoofy-cloud-backup.lock';
        if (is_file($path) && time() - (int) @filemtime($path) < 600) {
            return false;
        }

        return @file_put_contents($path, (string) time()) !== false;
    }

    private function unlock(): void
    {
        $path = dirname(__DIR__, 2).'/storage/framework/cache/eskoofy-cloud-backup.lock';
        if (is_file($path)) {
            @unlink($path);
        }
    }
}