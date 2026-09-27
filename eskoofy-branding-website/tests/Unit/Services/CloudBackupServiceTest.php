<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\Database;
use App\Services\CloudBackupService;
use Tests\FakeDatabase;
use Tests\TestCase;

class CloudBackupServiceTest extends TestCase
{
    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Settings::resetCache();
        $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));
        $this->db = new FakeDatabase();
        Database::setInstance($this->db);

        $this->db->seed('settings', [
            ['key' => 'cloud_backup.provider', 'value' => 'local'],
            ['key' => 'cloud_backup.credentials', 'value' => ''],
            ['key' => 'cloud_backup.folder', 'value' => 'eskoofy-backups'],
            ['key' => 'cloud_backup.enabled', 'value' => '1'],
            ['key' => 'cloud_backup.auto_enabled', 'value' => '0'],
            ['key' => 'cloud_backup.auto', 'value' => '{"interval_minutes":60,"keep":7}'],
        ]);
        $this->db->seed('cloud_backup_runs', []);
    }

    protected function tearDown(): void
    {
        \App\Models\Settings::resetCache();
        Database::setInstance(null);
        parent::tearDown();
    }

    public function test_settings_default_to_local_provider(): void
    {
        $settings = (new CloudBackupService($this->db))->settings();

        $this->assertSame('local', $settings['provider']);
        $this->assertTrue($settings['is_enabled']);
        $this->assertFalse($settings['auto_enabled']);
    }

    public function test_local_is_always_configured(): void
    {
        $this->assertTrue((new CloudBackupService($this->db))->isConfigured());
    }

    public function test_save_settings_persists_and_round_trips_credentials(): void
    {
        $service = new CloudBackupService($this->db);
        $service->saveSettings([
            'provider' => 'dropbox',
            'folder' => 'my-backups',
            'is_enabled' => true,
            'auto_enabled' => true,
            'interval_minutes' => 30,
            'keep' => 3,
            'credentials' => ['app_key' => 'k', 'app_secret' => 's'],
        ]);

        $settings = $service->settings();
        $this->assertSame('dropbox', $settings['provider']);
        $this->assertSame('my-backups', $settings['folder']);
        $this->assertSame('k', $settings['credentials']['app_key']);
        $this->assertSame('s', $settings['credentials']['app_secret']);
        $this->assertSame(30, $settings['interval_minutes']);
        $this->assertSame(3, $settings['keep']);
    }

    public function test_credentials_are_not_stored_in_plaintext(): void
    {
        $service = new CloudBackupService($this->db);
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'secret-value'],
        ]);

        $raw = '';
        foreach ($this->db->rows('settings') as $row) {
            if ($row['key'] === 'cloud_backup.credentials') {
                $raw = (string) $row['value'];
            }
        }
        $this->assertStringNotContainsString('secret-value', $raw, 'the raw row must not contain the plaintext credential');
    }

    public function test_a_blank_credential_keeps_the_stored_value(): void
    {
        $service = new CloudBackupService($this->db);
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => 's'],
        ]);
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => ''],
        ]);

        $settings = $service->settings();
        $this->assertSame('s', $settings['credentials']['app_secret']);
    }

    public function test_dispatch_skips_when_auto_is_off(): void
    {
        $result = (new CloudBackupService($this->db))->dispatch();

        $this->assertSame('skipped', $result['status']);
        $this->assertStringContainsString('off', $result['message']);
    }
}