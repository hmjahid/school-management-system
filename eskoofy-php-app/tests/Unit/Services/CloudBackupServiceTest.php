<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\Database;
use App\Services\CloudBackup\CloudBackupManager;
use App\Services\CloudBackup\CloudBackupService;
use App\Services\CloudBackup\Contracts\CloudHttpClient;
use Tests\Fakes\FakeDatabase;
use Tests\TestCase;

class CloudBackupServiceTest extends TestCase
{
    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new FakeDatabase();
        Database::setInstance($this->db);
        $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));
    }

    protected function tearDown(): void
    {
        Database::setInstance(null);
        parent::tearDown();
    }

    private function service(): CloudBackupService
    {
        $http = new class implements CloudHttpClient {
            public function request(string $method, string $url, array $headers = [], string|array|null $body = null): array
            {
                return ['status' => 200, 'body' => '{}', 'headers' => [], 'error' => null];
            }
        };

        return new CloudBackupService(new CloudBackupManager($http), $this->db);
    }

    public function test_settings_are_created_from_config_defaults_on_first_use(): void
    {
        $settings = $this->service()->settings();

        $this->assertSame(config('backup.default_provider'), $settings->provider);
        $this->assertSame(config('backup.folder'), $settings->folder);
        $this->assertNull($settings->last_run_at);
    }

    public function test_a_blank_credential_field_keeps_the_stored_secret(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'first', 'app_secret' => 'secret-1', 'refresh_token' => 'r1'],
        ]);

        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'first', 'app_secret' => '', 'refresh_token' => ''],
        ]);

        $credentials = $service->settings()->credentials;
        $this->assertSame('secret-1', $credentials['app_secret']);
        $this->assertSame('r1', $credentials['refresh_token']);
    }

    public function test_a_provider_switch_does_not_carry_a_secret_over_when_field_names_collide(): void
    {
        $service = $this->service();

        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => 's', 'refresh_token' => 'dropbox-token'],
        ]);

        $service->saveSettings([
            'provider' => 'google_drive',
            'credentials' => ['client_id' => 'cid', 'refresh_token' => ''],
        ]);

        $credentials = $service->settings()->credentials;

        $this->assertArrayNotHasKey('app_key', $credentials);
        $this->assertSame('cid', $credentials['client_id']);
        $this->assertArrayNotHasKey('refresh_token', $credentials, 'the Dropbox token must not survive the switch');
    }

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => 's'],
        ]);

        $raw = $this->db->tables['cloud_backup_settings'][0]['credentials'] ?? null;
        $this->assertIsString($raw);
        $this->assertStringStartsWith('esk1:', $raw);
        $this->assertStringNotContainsString('"app_key"', $raw, 'the raw row must not contain the plaintext JSON');
    }

    public function test_is_due_uses_last_run_and_interval(): void
    {
        $service = $this->service();
        $settings = $service->settings();
        $settings->last_run_at = date('Y-m-d H:i:s', time() - 3600);

        $this->assertTrue($service->isDue($settings, \App\Core\Support\Carbon::now()));

        $settings->last_run_at = date('Y-m-d H:i:s', time() - 60);
        $this->assertFalse($service->isDue($settings, \App\Core\Support\Carbon::now()));
    }

    public function test_dispatch_skips_when_automatic_backup_is_off(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'local', 'is_enabled' => true, 'auto_enabled' => false]);

        $result = $service->dispatch();
        $this->assertSame('skipped', $result['status']);
        $this->assertStringContainsString('off', $result['message']);
    }

    public function test_run_records_a_run_row(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'local', 'is_enabled' => true]);

        $result = $service->run();
        $this->assertSame('success', $result['status'], $result['message']);

        $this->assertArrayHasKey('cloud_backup_runs', $this->db->tables);
        $run = $this->db->tables['cloud_backup_runs'][0];
        $this->assertSame('success', $run['status']);
        $this->assertSame('local', $run['provider']);
    }
}