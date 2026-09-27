<?php

namespace Tests\Unit\Services\CloudBackup;

use App\Services\CloudBackup\CloudBackupManager;
use App\Services\CloudBackup\CloudBackupService;
use App\Services\CloudBackup\Contracts\CloudHttpClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Orchestration tests for settings persistence, the interval dispatcher, run
 * logging and retention. The `backup:run` step is stubbed out; what is under
 * test is the scheduler logic and the run log, not the portable zip.
 */
class CloudBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The local-cloud driver mirrors into the real filesystem (storage_path),
     * so it is given a throwaway folder that is removed again in tearDown.
     * The `local` *disk* is faked, so nothing here reaches the developer's
     * own storage/app/backups.
     */
    private const MIRROR_FOLDER = 'test-mirror';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('backups/cloud');
        File::deleteDirectory(storage_path('app/backups/cloud/'.self::MIRROR_FOLDER));

        parent::tearDown();
    }

    private function manager(?CloudHttpClient $http = null): CloudBackupManager
    {
        $manager = new CloudBackupManager($http ?? new FakeCloudHttpClient);

        $this->app->instance(CloudBackupManager::class, $manager);

        return $manager;
    }

    private function service(?CloudHttpClient $http = null): CloudBackupService
    {
        return new CloudBackupService($this->manager($http));
    }

    #[Test]
    public function settings_are_created_from_config_defaults_on_first_use(): void
    {
        $settings = $this->service()->settings();

        $this->assertSame(config('backup.default_provider'), $settings->provider);
        $this->assertSame(config('backup.folder'), $settings->folder);
        $this->assertNull($settings->last_run_at);
    }

    #[Test]
    public function a_blank_credential_field_keeps_the_stored_secret(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'first', 'app_secret' => 'secret-1', 'refresh_token' => 'r1'],
        ]);

        // The form posts every field; secrets are never rendered back, so an
        // empty input must mean "unchanged" and not wipe the stored value.
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'first', 'app_secret' => '', 'refresh_token' => ''],
        ]);

        $credentials = (array) $service->settings()->credentials;
        $this->assertSame('secret-1', $credentials['app_secret']);
        $this->assertSame('r1', $credentials['refresh_token']);
    }

    #[Test]
    public function switching_provider_drops_the_previous_providers_credentials(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => 's', 'refresh_token' => 'r'],
        ]);
        $service->saveSettings([
            'provider' => '4shared',
            'credentials' => ['api_key' => 'fs'],
        ]);

        $this->assertSame(['api_key' => 'fs'], (array) $service->settings()->credentials);
    }

    #[Test]
    public function interval_and_retention_are_clamped_to_the_configured_bounds(): void
    {
        $service = $this->service();

        $service->saveSettings(['provider' => 'local', 'interval_minutes' => 1, 'keep' => 9999]);
        $this->assertSame((int) config('backup.auto.min_interval_minutes'), $service->settings()->interval_minutes);
        $this->assertSame((int) config('backup.auto.max_keep'), $service->settings()->keep);

        $service->saveSettings(['provider' => 'local', 'interval_minutes' => 999999, 'keep' => 0]);
        $this->assertSame((int) config('backup.auto.max_interval_minutes'), $service->settings()->interval_minutes);
        $this->assertSame((int) config('backup.auto.min_keep'), $service->settings()->keep);
    }

    #[Test]
    public function an_unknown_provider_falls_back_to_the_default(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'mega-drive']);

        $this->assertSame(config('backup.default_provider'), $service->settings()->provider);
    }

    #[Test]
    public function the_dispatcher_only_uploads_once_the_interval_has_elapsed(): void
    {
        $service = $this->service();
        $settings = $service->saveSettings([
            'provider' => 'local',
            'auto_enabled' => true,
            'interval_minutes' => 15,
        ]);

        $settings->forceFill(['last_run_at' => now()->subMinutes(5)])->save();
        $result = $service->dispatch(now()->addMinute());

        $this->assertSame('skipped', $result['status']);
        // A decline must not restart the interval clock, or the interval would
        // never be reached (every tick would push last_run_at forward).
        $this->assertTrue(
            $service->settings()->last_run_at->between(now()->subMinutes(6), now()->subMinutes(4)),
            'last_run_at must not move when the dispatcher skips'
        );
    }

    #[Test]
    public function a_not_due_dispatcher_tick_writes_no_run_row(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'local', 'auto_enabled' => true, 'interval_minutes' => 60]);
        $service->settings()->forceFill(['last_run_at' => now()])->save();

        $before = \App\Models\CloudBackupRun::query()->count();
        $service->dispatch();
        $this->assertSame($before, \App\Models\CloudBackupRun::query()->count());
    }

    #[Test]
    public function the_dispatcher_skips_when_automatic_backup_is_off(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'local', 'auto_enabled' => false]);

        $result = $service->dispatch();

        $this->assertSame('skipped', $result['status']);
        $this->assertStringContainsString('off', $result['message']);
    }

    #[Test]
    public function a_disabled_install_is_skipped_and_does_not_move_the_clock(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'local', 'is_enabled' => false]);
        $service->settings()->forceFill(['last_run_at' => now()->subDay()])->save();

        $result = $service->run();

        $this->assertSame('skipped', $result['status']);
        $this->assertTrue($service->settings()->last_run_at->isBefore(now()->subHours(1)));
    }

    #[Test]
    public function a_provider_without_complete_credentials_is_skipped_not_attempted(): void
    {
        $http = new FakeCloudHttpClient;
        $service = $this->service($http);
        $service->saveSettings([
            'provider' => 'google_drive',
            'credentials' => ['client_id' => 'only-half'],
        ]);

        $result = $service->run();

        $this->assertSame('skipped', $result['status']);
        $this->assertStringContainsString('credential', $result['message']);
        $this->assertSame([], $http->requests, 'an unconfigured provider must not be called');
    }

    #[Test]
    public function a_successful_run_records_the_remote_key_and_prunes(): void
    {
        $zip = $this->fakeZip();
        $service = new StubCloudBackupService($this->manager(), $zip);

        $service->saveSettings(['provider' => 'local', 'keep' => 1]);
        $result = $service->run();

        @unlink($zip);

        $this->assertSame('success', $result['status'], $result['message']);
        $run = \App\Models\CloudBackupRun::query()->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame('success', $run->status);
        $this->assertNotNull($service->settings()->last_run_at);
        $this->assertTrue($service->settings()->last_run_at->isAfter(now()->subMinute()));
    }

    #[Test]
    public function a_failed_run_is_logged_with_the_error_and_the_clock_still_moves(): void
    {
        $service = new StubCloudBackupService($this->manager(), null, new \RuntimeException('disk full'));
        $service->saveSettings(['provider' => 'local']);

        $result = $service->run();

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('disk full', $result['message']);
        $this->assertSame('disk full', $service->settings()->last_error);
        $this->assertTrue($service->settings()->last_run_at->isAfter(now()->subMinute()));
    }

    #[Test]
    public function credentials_merge_env_fallbacks_under_stored_values(): void
    {
        $manager = new CloudBackupManager(new FakeCloudHttpClient);

        config()->set('backup.env.4shared', ['api_key' => 'from-env', 'username' => 'env-user']);

        $merged = $manager->credentialsFor('4shared', ['api_key' => 'from-db']);

        $this->assertSame('from-db', $merged['api_key'], 'a stored secret must win over the env fallback');
        $this->assertSame('env-user', $merged['username']);
    }

    #[Test]
    public function credentials_are_encrypted_at_rest_but_readable_through_the_model(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 's3', 'credentials' => ['secret' => 'super-secret']]);

        $id = $service->settings()->id;

        // What is actually in the column.
        $raw = \Illuminate\Support\Facades\DB::table('cloud_backup_settings')->where('id', $id)->value('credentials');
        $this->assertIsString($raw);
        $this->assertStringNotContainsString('super-secret', $raw, 'the secret must not be stored in the clear');
        $this->assertNotSame('super-secret', $raw);

        // And what the app reads back.
        $this->assertSame('super-secret', \App\Models\CloudBackupSetting::query()->find($id)->credentials['secret']);
    }

    #[Test]
    public function configured_fields_report_presence_never_the_value(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'dropbox', 'credentials' => ['app_key' => 'k', 'app_secret' => 's', 'refresh_token' => 'r']]);

        $configured = $service->settings()->configuredFields();

        $this->assertTrue($configured['app_key']);
        $this->assertFalse($configured['access_token']);
        $this->assertNotContains('k', $configured, 'configuredFields() must only report booleans');
    }

    #[Test]
    public function a_service_account_only_install_counts_as_configured(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'google_drive',
            'credentials' => [
                'service_account_email' => 'backup@example.iam.gserviceaccount.com',
                'service_account_private_key' => '-----BEGIN PRIVATE KEY-----',
            ],
        ]);

        $this->assertTrue($service->settings()->hasRequiredCredentials());
    }

    // ------------------------------------------------------------- real archives

    #[Test]
    public function the_portable_archive_is_the_one_just_created_not_an_older_one(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'local', 'is_enabled' => true]);

        // A stale archive whose name sorts *above* the new one. Resolving "the
        // file backup:run just made" by sorting the folder by name would upload
        // this instead.
        Storage::disk('local')->put('backups/zzz_older_archive.zip', 'stale');
        Storage::disk('local')->put('backups/aaa_even_older.zip', 'stale');

        $path = $service->createPortableBackup();

        $this->assertFileExists($path);
        $this->assertStringStartsWith(
            'backup_',
            basename($path),
            'the archive must be the one backup:run reported, not a pre-existing file'
        );
        $this->assertStringNotContainsString('stale', basename($path));

        Storage::disk('local')->delete($path);
    }

    #[Test]
    public function a_run_discards_its_staged_archive_after_a_successful_upload(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'local',
            'is_enabled' => true,
            'folder' => self::MIRROR_FOLDER,
        ]);

        $result = $service->run();
        $this->assertSame('success', $result['status'], $result['message']);

        $staging = Storage::disk('local')->path((string) config('backup.scratch_dir'));
        $this->assertSame(
            [],
            array_values(array_diff(scandir($staging) ?: [], ['.', '..'])),
            'a successful upload must not leave a full copy of the archive on the server'
        );

        // The uploaded copy is the one that survives. The local driver mirrors to
        // the real filesystem, not to the `local` disk.
        $this->assertFileExists(
            storage_path('app/backups/cloud/'.self::MIRROR_FOLDER.'/'.$result['file'])
        );
    }

    #[Test]
    public function a_run_that_never_completes_does_not_grow_the_staging_dir_without_bound(): void
    {
        $service = new StubCloudBackupService($this->manager());
        $service->saveSettings(['provider' => 'local', 'is_enabled' => true, 'folder' => 'staging-test']);
        $service->failing = true;

        $staging = config('backup.scratch_dir');
        for ($i = 0; $i < 6; $i++) {
            $zip = tempnam(sys_get_temp_dir(), 'esk-stage').'.zip';
            file_put_contents($zip, 'x');
            Storage::disk('local')->put($staging.'/staged_'.$i.'.zip', 'x');
            @unlink($zip);
        }

        $service->run();

        $remaining = collect(Storage::disk('local')->files($staging))->count();
        $this->assertLessThanOrEqual(3, $remaining, 'the staging directory must be capped');

        Storage::disk('local')->deleteDirectory($staging);
    }

    #[Test]
    public function local_retention_orders_by_modification_time_not_by_name(): void
    {
        $disk = Storage::disk('local');

        // Name order and mtime order disagree: the newest file sorts first by
        // name, the oldest sorts first alphabetically.
        $disk->put('backups/zzz_newest_by_name.zip', 'a');
        $disk->put('backups/aaa_oldest_by_name.zip', 'b');
        touch($disk->path('backups/zzz_newest_by_name.zip'), now()->subMinute()->getTimestamp());
        touch($disk->path('backups/aaa_oldest_by_name.zip'), now()->subDay()->getTimestamp());

        $removed = $this->service()->pruneLocal(1);

        $this->assertSame(1, $removed);
        $this->assertTrue($disk->exists('backups/zzz_newest_by_name.zip'), 'the newest archive must be the one kept');
        $this->assertFalse($disk->exists('backups/aaa_oldest_by_name.zip'));

        $disk->deleteDirectory('backups');
    }

    // ------------------------------------------------------------- remote list

    #[Test]
    public function listing_explains_why_it_is_empty(): void
    {
        $service = $this->service();

        // No credentials yet.
        $service->saveSettings(['provider' => 'dropbox']);
        $listing = $service->listRemote();
        $this->assertFalse($listing['ok']);
        $this->assertSame([], $listing['files']);
        $this->assertStringContainsString('credential', $listing['message']);

        // Disabled install.
        $service->saveSettings(['provider' => 'local', 'is_enabled' => false]);
        $listing = $service->listRemote();
        $this->assertFalse($listing['ok']);
        $this->assertStringContainsString('disabled', $listing['message']);

        // Configured and working, but nothing uploaded yet.
        $service->saveSettings(['provider' => 'local', 'is_enabled' => true, 'folder' => 'empty-folder']);
        $listing = $service->listRemote();
        $this->assertTrue($listing['ok']);
        $this->assertSame([], $listing['files']);
        $this->assertStringContainsString('No backups', $listing['message']);
    }

    #[Test]
    public function the_local_listing_is_scoped_to_the_configured_folder(): void
    {
        $service = $this->service();
        $service->saveSettings(['provider' => 'local', 'is_enabled' => true, 'folder' => self::MIRROR_FOLDER]);

        File::ensureDirectoryExists(storage_path('app/backups/cloud/'.self::MIRROR_FOLDER));
        File::put(storage_path('app/backups/cloud/'.self::MIRROR_FOLDER.'/one.zip'), 'a');
        File::ensureDirectoryExists(storage_path('app/backups/cloud/other-install'));
        File::put(storage_path('app/backups/cloud/other-install/two.zip'), 'b');

        $names = array_column($service->listRemote()['files'], 'name');
        $this->assertSame(['one.zip'], $names, 'another install\'s folder must not leak into this listing');

        File::deleteDirectory(storage_path('app/backups/cloud/other-install'));
    }

    #[Test]
    public function a_provider_switch_does_not_carry_a_secret_over_when_field_names_collide(): void
    {
        $service = $this->service();

        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => 's', 'refresh_token' => 'dropbox-token'],
        ]);

        // google_drive also has a "refresh_token" field. Carrying the stored
        // value over would file a Dropbox token under Google Drive, where it is
        // sent to a completely different host.
        $service->saveSettings([
            'provider' => 'google_drive',
            'credentials' => ['client_id' => 'cid', 'refresh_token' => ''],
        ]);

        $credentials = $service->settings()->credentials;

        $this->assertArrayNotHasKey('app_key', $credentials);
        $this->assertArrayNotHasKey('app_secret', $credentials);
        $this->assertSame('cid', $credentials['client_id']);
        $this->assertArrayNotHasKey('refresh_token', $credentials, 'the Dropbox token must not survive the switch');
    }

    #[Test]
    public function a_half_filled_preview_keeps_the_stored_secret(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'dropbox',
            'folder' => 'keep-me',
            'credentials' => ['app_key' => 'k', 'app_secret' => 's', 'refresh_token' => 'r'],
        ]);

        $preview = $service->previewSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => '', 'refresh_token' => ''],
        ]);

        $this->assertSame('s', $preview->credentials['app_secret'], 'a blank field means unchanged');
        $this->assertSame('r', $preview->credentials['refresh_token']);
        $this->assertSame('keep-me', $preview->folder);

        // ...and nothing was written.
        $this->assertDatabaseCount('cloud_backup_settings', 1);
    }

    #[Test]
    public function a_preview_for_a_different_provider_borrows_nothing(): void
    {
        $service = $this->service();
        $service->saveSettings([
            'provider' => 'dropbox',
            'credentials' => ['app_key' => 'k', 'app_secret' => 's', 'refresh_token' => 'dropbox-token'],
        ]);

        $preview = $service->previewSettings([
            'provider' => 's3',
            'credentials' => ['bucket' => 'b'],
        ]);

        $this->assertSame(['bucket' => 'b'], $preview->credentials);
        $this->assertSame('s3', $preview->provider);
    }

    private function fakeZip(): string
    {
        $zip = tempnam(sys_get_temp_dir(), 'esk-run').'.zip';
        file_put_contents($zip, 'zip-bytes');

        return $zip;
    }
}

/**
 * Replaces the portable-zip step, which is not what these tests are about,
 * with a fixed file (or a thrown error).
 */
class StubCloudBackupService extends CloudBackupService
{
    public function __construct(
        CloudBackupManager $manager,
        private ?string $zip = null,
        private ?\Throwable $failure = null,
    ) {
        parent::__construct($manager);
    }

    /** Set to make createPortableBackup() blow up without a zip on disk. */
    public bool $failing = false;

    public function createPortableBackup(): string
    {
        if ($this->failing) {
            throw new \RuntimeException('staging failed');
        }

        if ($this->failure !== null) {
            throw $this->failure;
        }

        return (string) $this->zip;
    }
}
