<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CloudBackup\CloudBackupManager;
use App\Services\CloudBackup\Contracts\CloudHttpClient;
use Tests\TestCase;

class CloudBackupManagerTest extends TestCase
{
    private function manager(): CloudBackupManager
    {
        $http = new class implements CloudHttpClient {
            public function request(string $method, string $url, array $headers = [], string|array|null $body = null): array
            {
                return ['status' => 200, 'body' => '{}', 'headers' => [], 'error' => null];
            }
        };

        return new CloudBackupManager($http);
    }

    public function test_known_providers(): void
    {
        $this->assertTrue(CloudBackupManager::isKnown('local'));
        $this->assertTrue(CloudBackupManager::isKnown('google_drive'));
        $this->assertTrue(CloudBackupManager::isKnown('dropbox'));
        $this->assertTrue(CloudBackupManager::isKnown('4shared'));
        $this->assertTrue(CloudBackupManager::isKnown('s3'));
        $this->assertFalse(CloudBackupManager::isKnown('ftp'));
    }

    public function test_local_is_always_configured(): void
    {
        $this->assertTrue($this->manager()->isConfigured('local', []));
    }

    public function test_service_account_only_google_drive_counts_as_configured(): void
    {
        $manager = $this->manager();
        $this->assertFalse($manager->isConfigured('google_drive', []));
        $this->assertTrue($manager->isConfigured('google_drive', [
            'service_account_email' => 'a@b.iam.gserviceaccount.com',
            'service_account_private_key' => '-----BEGIN PRIVATE KEY-----',
        ]));
        $this->assertTrue($manager->isConfigured('google_drive', [
            'client_id' => 'cid', 'client_secret' => 'cs', 'refresh_token' => 'rt',
        ]));
    }

    public function test_dropbox_access_token_alternative_counts_as_configured(): void
    {
        $manager = $this->manager();
        $this->assertTrue($manager->isConfigured('dropbox', ['access_token' => 't']));
        $this->assertFalse($manager->isConfigured('dropbox', ['app_key' => 'k']));
    }

    public function test_env_fallbacks_merge_under_stored_values(): void
    {
        $manager = $this->manager();
        $merged = $manager->credentialsFor('s3', ['key' => 'stored-key']);

        $this->assertSame('stored-key', $merged['key']);
        // The env fallback for the remaining s3 fields is null by default, so
        // the merged set must not include empty values.
        $this->assertArrayNotHasKey('secret', $merged);
    }

    public function test_driver_unknown_provider_falls_back_to_local(): void
    {
        $driver = $this->manager()->driver('nope');

        $this->assertSame('local', $driver->key());
    }
}