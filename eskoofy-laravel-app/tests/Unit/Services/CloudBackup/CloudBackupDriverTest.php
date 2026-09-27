<?php

namespace Tests\Unit\Services\CloudBackup;

use App\Services\CloudBackup\Contracts\CloudBackupDriver;
use App\Services\CloudBackup\Drivers\DropboxDriver;
use App\Services\CloudBackup\Drivers\FourSharedDriver;
use App\Services\CloudBackup\Drivers\GoogleDriveDriver;
use App\Services\CloudBackup\Drivers\S3CloudDriver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Driver-level tests. Every provider is exercised through the same fake
 * transport, so the four verbs are asserted to produce the HTTP calls the
 * provider's API actually requires — without a single network request.
 */
class CloudBackupDriverTest extends TestCase
{
    private string $zipPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zipPath = tempnam(sys_get_temp_dir(), 'esk').'.zip';
        file_put_contents($this->zipPath, "PK\x03\x04fake-zip-bytes");
    }

    protected function tearDown(): void
    {
        @unlink($this->zipPath);

        parent::tearDown();
    }

    // ------------------------------------------------------------- google drive

    #[Test]
    public function google_drive_uploads_to_the_resolved_folder_id(): void
    {
        $http = (new FakeCloudHttpClient)->queue([
            ['body' => json_encode(['access_token' => 'ya29.test'])],       // refresh token exchange
            ['body' => json_encode(['files' => []])],                        // folder lookup: not found
            ['body' => json_encode(['id' => 'folder-123'])],                  // folder create
            ['status' => 200, 'body' => json_encode(['id' => 'file-9', 'name' => 'backup.zip', 'size' => 16])],
        ]);

        $driver = new GoogleDriveDriver($http, [
            'client_id' => 'cid',
            'client_secret' => 'csecret',
            'refresh_token' => 'refresh',
        ], 'eskoofy-backups');

        $result = $driver->upload($this->zipPath, 'backup.zip');

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame('file-9', $result['id']);

        $upload = $http->requests[count($http->requests) - 1];
        // The parent must be the folder *id*; Drive rejects a folder name.
        $this->assertStringContainsString('"parents":["folder-123"]', (string) $upload['body']);
        $this->assertSame('Bearer ya29.test', $upload['headers']['Authorization']);
    }

    #[Test]
    public function google_drive_service_account_mode_signs_a_jwt_instead_of_sending_the_private_key(): void
    {
        [$privateKey] = $this->serviceAccountKeyPair();

        $http = (new FakeCloudHttpClient)->queue([
            ['body' => json_encode(['access_token' => 'ya29.signed'])],
            ['body' => json_encode(['files' => [['id' => 'folder-123']]])],
            ['body' => json_encode(['id' => 'file-9', 'name' => 'backup.zip', 'size' => 16])],
        ]);

        $driver = new GoogleDriveDriver($http, [
            'service_account_email' => 'backup@example.iam.gserviceaccount.com',
            'service_account_private_key' => $privateKey,
        ], 'eskoofy-backups');

        $result = $driver->upload($this->zipPath, 'backup.zip');
        $this->assertArrayNotHasKey('error', $result);

        $tokenRequest = $http->requests[0];
        $this->assertStringContainsString(
            'grant_type=urn%3Aietf%3Aparams%3Aoauth%3Agrant-type%3Ajwt-bearer',
            (string) $tokenRequest['body']
        );
        // The private key must never leave the process.
        $this->assertStringNotContainsString($privateKey, json_encode($http->requests));
        $this->assertStringNotContainsString('BEGIN PRIVATE KEY', json_encode($http->requests));

        // ...and the token is used as a bearer credential, not as the key.
        $this->assertSame('Bearer ya29.signed', $http->requests[1]['headers']['Authorization']);
    }

    #[Test]
    public function google_drive_lists_by_folder_id_and_deletes_by_id(): void
    {
        $http = (new FakeCloudHttpClient)->queue([
            ['body' => json_encode(['access_token' => 't'])],
            ['body' => json_encode(['files' => [['id' => 'folder-123']]])],
            ['body' => json_encode(['files' => [
                ['id' => 'f2', 'name' => 'b.zip', 'size' => 2, 'modifiedTime' => '2026-01-02T00:00:00Z'],
                ['id' => 'f1', 'name' => 'a.zip', 'size' => 1, 'modifiedTime' => '2026-01-01T00:00:00Z'],
            ]])],
            ['status' => 204],
        ]);

        $driver = new GoogleDriveDriver($http, ['refresh_token' => 'r', 'client_id' => 'c'], 'eskoofy-backups');

        $files = $driver->list();
        $this->assertSame(['f2', 'f1'], array_column($files, 'id'));
        // http_build_query percent-encodes the query, so compare the decoded value.
        parse_str((string) parse_url($http->requests[2]['url'], PHP_URL_QUERY), $query);
        $this->assertSame("'folder-123' in parents and trashed = false", $query['q']);

        $this->assertTrue($driver->delete('f2'));
        $this->assertStringEndsWith('/files/f2', $http->requests[3]['url']);
    }

    // ------------------------------------------------------------------ dropbox

    #[Test]
    public function dropbox_stores_the_path_because_download_and_delete_need_one(): void
    {
        // An `access_token` credential is used verbatim, so there is no token
        // exchange to queue.
        $http = (new FakeCloudHttpClient)->queue([
            ['status' => 200, 'body' => json_encode([
                'id' => 'id:AAAA',
                'name' => 'backup.zip',
                'size' => 16,
                'path_display' => '/eskoofy-backups/backup.zip',
            ])],
        ]);

        $driver = new DropboxDriver($http, ['access_token' => 'sl.token'], 'eskoofy-backups');
        $result = $driver->upload($this->zipPath, 'backup.zip');

        $this->assertArrayNotHasKey('error', $result);
        // The value that later download()/delete() calls will receive.
        $this->assertSame('/eskoofy-backups/backup.zip', $driver->remoteKey($result));
    }

    #[Test]
    public function dropbox_list_returns_paths_as_ids(): void
    {
        $http = (new FakeCloudHttpClient)->queue([
            ['body' => json_encode(['entries' => [
                ['.tag' => 'file', 'id' => 'id:AAAA', 'path_display' => '/eskoofy-backups/b.zip', 'name' => 'b.zip', 'size' => 2, 'server_modified' => '2026-02-01T00:00:00Z'],
                ['.tag' => 'folder', 'id' => 'id:DDDD', 'name' => 'sub'],
            ]])],
        ]);

        $driver = new DropboxDriver($http, ['access_token' => 't'], 'eskoofy-backups');
        $files = $driver->list();

        $this->assertCount(1, $files, 'folders are not backup archives');
        $this->assertSame('/eskoofy-backups/b.zip', $files[0]['id']);
        $this->assertSame('id:AAAA', $files[0]['dropbox_id']);
    }

    #[Test]
    public function dropbox_download_writes_the_response_body(): void
    {
        $http = (new FakeCloudHttpClient)->queue([
            ['status' => 200, 'body' => 'zip-bytes'],
        ]);

        $target = tempnam(sys_get_temp_dir(), 'esk-dl');
        $driver = new DropboxDriver($http, ['access_token' => 't'], 'eskoofy-backups');

        $this->assertSame($target, $driver->download('/eskoofy-backups/backup.zip', $target));
        $this->assertSame('zip-bytes', file_get_contents($target));

        @unlink($target);
    }

    // ----------------------------------------------------------------- 4shared

    #[Test]
    public function fourshared_uploads_a_real_multipart_file_part(): void
    {
        $http = (new FakeCloudHttpClient)->queue([
            ['body' => json_encode(['id' => 'fs-77', 'filename' => 'backup.zip', 'size' => 16])],
        ]);

        $driver = new FourSharedDriver($http, ['api_key' => 'key'], 'eskoofy-backups');
        $result = $driver->upload($this->zipPath, 'backup.zip');

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame('fs-77', $result['id']);

        $body = $http->lastRequest()['body'];
        $this->assertIsArray($body);
        // The file part must describe a path on disk — a nested descriptor with
        // raw contents would be cast to the string "Array".
        $this->assertSame($this->zipPath, $body['file']['path']);
        $this->assertSame('application/zip', $body['file']['mime']);
        $this->assertSame('eskoofy-backups', $body['folder']);
        $this->assertSame('key', $http->lastRequest()['headers']['X-API-KEY']);
    }

    #[Test]
    public function fourshared_reports_a_provider_error_instead_of_throwing(): void
    {
        $http = (new FakeCloudHttpClient)->queue([['status' => 401, 'body' => json_encode(['error' => 'bad key'])]]);

        $driver = new FourSharedDriver($http, ['api_key' => 'key'], 'eskoofy-backups');
        $result = $driver->upload($this->zipPath, 'backup.zip');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('401', (string) $result['error']);
    }

    // ---------------------------------------------------------------------- s3

    #[Test]
    public function s3_sends_the_x_amz_headers_it_signed(): void
    {
        $http = (new FakeCloudHttpClient)->queue([
            ['status' => 200, 'body' => '<?xml version="1.0"?><ListBucketResult></ListBucketResult>'],
        ]);

        $driver = new S3CloudDriver($http, [
            'bucket' => 'my-backups',
            'key' => 'AKIAEXAMPLE',
            'secret' => 'shhh',
            'region' => 'eu-west-1',
        ], 'eskoofy-backups');

        $this->assertTrue($driver->test()['ok']);

        $request = $http->lastRequest();
        $this->assertStringStartsWith('AWS4-HMAC-SHA256 Credential=AKIAEXAMPLE/', $request['headers']['Authorization']);
        // A signature is only valid if these are actually sent.
        $this->assertArrayHasKey('x-amz-date', $request['headers']);
        $this->assertArrayHasKey('x-amz-content-sha256', $request['headers']);
        $this->assertStringContainsString('/eu-west-1/s3/aws4_request', $request['headers']['Authorization']);
    }

    #[Test]
    public function s3_list_query_is_canonically_encoded_and_sorted(): void
    {
        $http = (new FakeCloudHttpClient)->queue([
            ['status' => 200, 'body' => json_encode(['Contents' => [
                ['Key' => 'eskoofy-backups/b.zip', 'Size' => 2, 'LastModified' => '2026-01-02T00:00:00Z'],
                ['Key' => 'eskoofy-backups/a.zip', 'Size' => 1, 'LastModified' => '2026-01-01T00:00:00Z'],
            ]])],
        ]);

        $driver = new S3CloudDriver($http, [
            'bucket' => 'my-backups', 'key' => 'k', 'secret' => 's', 'region' => 'us-east-1',
        ], 'eskoofy-backups');

        $files = $driver->list();
        $this->assertSame(['eskoofy-backups/b.zip', 'eskoofy-backups/a.zip'], array_column($files, 'id'));
        $this->assertStringContainsString('list-type=2&max-keys=200&prefix=eskoofy-backups%2F', $http->lastRequest()['url']);
    }

    // ----------------------------------------------------------------- shared

    #[Test]
    public function every_driver_reports_a_missing_file_without_throwing(): void
    {
        $http = new FakeCloudHttpClient;

        foreach ($this->drivers($http) as $name => $driver) {
            /** @var CloudBackupDriver $driver */
            $result = $driver->upload('/tmp/eskoofy-does-not-exist.zip', 'x.zip');
            $this->assertArrayHasKey('error', $result, $name.' must not throw on a missing file');
        }

        $this->assertSame([], $http->requests, 'no HTTP call should be made without a file');
    }

    #[Test]
    public function remote_keys_default_to_the_provider_id(): void
    {
        $http = new FakeCloudHttpClient;

        $this->assertSame('abc', (new S3CloudDriver($http, []))->remoteKey(['id' => 'abc']));
        $this->assertSame('abc', (new GoogleDriveDriver($http, []))->remoteKey(['id' => 'abc', 'path' => 'ignored']));
        $this->assertSame('/p/b.zip', (new DropboxDriver($http, []))->remoteKey(['id' => 'id:X', 'path' => '/p/b.zip']));
        $this->assertSame('id:X', (new DropboxDriver($http, []))->remoteKey(['id' => 'id:X']));
    }

    #[Test]
    public function folder_and_file_names_are_sanitised(): void
    {
        $http = new FakeCloudHttpClient;
        $driver = new FourSharedDriver($http, [], '../../etc/pa ss');

        $this->assertSame('etc/pa-ss', $driver->folder());
        $this->assertSame('backup.zip', $driver->sanitizeFileName('../../backup.zip'));
        $this->assertSame('a-b.zip', $driver->sanitizeFileName('a b.zip'));
    }

    // --------------------------------------------------------------- providers

    /**
     * @return array<string, CloudBackupDriver>
     */
    private function drivers(FakeCloudHttpClient $http): array
    {
        return [
            'google_drive' => new GoogleDriveDriver($http, ['client_id' => 'c', 'client_secret' => 's', 'refresh_token' => 'r']),
            'dropbox' => new DropboxDriver($http, ['app_key' => 'k', 'app_secret' => 's', 'refresh_token' => 'r']),
            '4shared' => new FourSharedDriver($http, ['api_key' => 'k']),
            's3' => new S3CloudDriver($http, ['bucket' => 'b', 'key' => 'k', 'secret' => 's']),
        ];
    }

    /** @return array{0: string, 1: string} */
    private function serviceAccountKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privateKey);

        return [$privateKey, '-----BEGIN PUBLIC KEY-----'];
    }
}
