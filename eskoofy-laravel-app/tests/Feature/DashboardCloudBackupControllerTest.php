<?php

namespace Tests\Feature;

use App\Models\CloudBackupRun;
use App\Models\CloudBackupSetting;
use App\Models\User;
use App\Services\CloudBackup\CloudBackupManager;
use App\Services\CloudBackup\CloudBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Unit\Services\CloudBackup\FakeCloudHttpClient;

class DashboardCloudBackupControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string ...$abilities): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        foreach ($abilities as $ability) {
            $user->givePermissionTo(Permission::findOrCreate($ability, 'web'));
        }

        return $user;
    }

    private function bindFakeTransport(): FakeCloudHttpClient
    {
        $http = new FakeCloudHttpClient;
        $manager = new CloudBackupManager($http);
        $this->app->instance(CloudBackupManager::class, $manager);
        $this->app->instance(CloudBackupService::class, new CloudBackupService($manager));

        return $http;
    }

    #[Test]
    public function the_settings_page_is_shown_to_an_authorised_user(): void
    {
        $this->actingAs($this->admin('manage_cloud_backup'))
            ->get(route('dashboard.cloud-backup.index'))
            ->assertOk()
            ->assertSee('Google Drive')
            ->assertSee('4shared')
            // Never the stored secret, and never a password value.
            ->assertDontSee('super-secret', false);
    }

    #[Test]
    public function a_user_without_the_permission_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.cloud-backup.index'))
            ->assertForbidden();
    }

    #[Test]
    public function a_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard.cloud-backup.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function saved_credentials_are_never_rendered_back(): void
    {
        $this->actingAs($this->admin('manage_cloud_backup'))->post(route('dashboard.cloud-backup.update'), [
            'provider' => '4shared',
            'folder' => 'eskoofy-backups',
            'is_enabled' => '1',
            'credentials' => ['api_key' => 'super-secret', 'username' => 'admin', 'password' => 'hunter2'],
        ])->assertRedirect();

        $stored = CloudBackupSetting::query()->first();
        $this->assertSame('4shared', $stored->provider);
        $this->assertSame('super-secret', $stored->credentials['api_key']);

        // The page shows presence, not values.
        $this->actingAs($this->admin('manage_cloud_backup'))
            ->get(route('dashboard.cloud-backup.index'))
            ->assertOk()
            ->assertSee('saved', false)
            ->assertDontSee('super-secret', false)
            ->assertDontSee('hunter2', false);
    }

    #[Test]
    public function a_blank_credential_field_keeps_the_stored_secret(): void
    {
        $user = $this->admin('manage_cloud_backup');

        $this->actingAs($user)->post(route('dashboard.cloud-backup.update'), [
            'provider' => '4shared',
            'credentials' => ['api_key' => 'first-key'],
        ])->assertRedirect();

        $this->actingAs($user)->post(route('dashboard.cloud-backup.update'), [
            'provider' => '4shared',
            'credentials' => ['api_key' => '', 'username' => 'admin'],
        ])->assertRedirect();

        $stored = CloudBackupSetting::query()->first();
        $this->assertSame('first-key', $stored->credentials['api_key']);
        $this->assertSame('admin', $stored->credentials['username']);
    }

    #[Test]
    public function the_schedule_is_clamped_to_the_configured_bounds(): void
    {
        $this->actingAs($this->admin('manage_cloud_backup'))->post(route('dashboard.cloud-backup.update'), [
            'provider' => 'local',
            'interval_minutes' => 1,
            'keep' => 9999,
        ])->assertRedirect();

        $stored = CloudBackupSetting::query()->first();
        $this->assertSame((int) config('backup.auto.min_interval_minutes'), $stored->interval_minutes);
        $this->assertSame((int) config('backup.auto.max_keep'), $stored->keep);
    }

    #[Test]
    public function an_unknown_provider_is_rejected(): void
    {
        $this->actingAs($this->admin('manage_cloud_backup'))
            ->post(route('dashboard.cloud-backup.update'), ['provider' => 'mega-drive'])
            ->assertSessionHasErrors('provider');
    }

    #[Test]
    public function the_connection_test_reports_success_for_the_local_provider(): void
    {
        $this->bindFakeTransport();

        $this->actingAs($this->admin('manage_cloud_backup'))
            ->from(route('dashboard.cloud-backup.index'))
            ->post(route('dashboard.cloud-backup.test'))
            ->assertRedirect(route('dashboard.cloud-backup.index'))
            ->assertSessionHas('cloudResult')
            ->assertSessionHas('cloudResult.ok', true);
    }

    #[Test]
    public function the_connection_test_reports_a_provider_failure(): void
    {
        $http = $this->bindFakeTransport();
        $http->queue([['status' => 401, 'body' => '{"error":"bad token"}']]);

        $this->actingAs($this->admin('manage_cloud_backup'))->post(route('dashboard.cloud-backup.update'), [
            'provider' => '4shared',
            'credentials' => ['api_key' => 'k', 'username' => 'u', 'password' => 'p'],
        ]);

        $this->actingAs($this->admin('manage_cloud_backup'))
            ->from(route('dashboard.cloud-backup.index'))
            ->post(route('dashboard.cloud-backup.test'))
            ->assertSessionHas('cloudResult.ok', false);
    }

    #[Test]
    public function a_provider_without_credentials_is_reported_before_any_request(): void
    {
        $http = $this->bindFakeTransport();

        $this->actingAs($this->admin('manage_cloud_backup'))->post(route('dashboard.cloud-backup.update'), [
            'provider' => '4shared',
        ]);

        $this->actingAs($this->admin('manage_cloud_backup'))
            ->from(route('dashboard.cloud-backup.index'))
            ->post(route('dashboard.cloud-backup.test'))
            ->assertSessionHas('cloudResult.ok', false);

        $this->assertSame([], $http->requests, 'no HTTP call may be made without credentials');
    }

    #[Test]
    public function a_manual_run_uploads_and_records_history(): void
    {
        $this->bindFakeTransport();
        $user = $this->admin('manage_cloud_backup');

        $this->actingAs($user)->post(route('dashboard.cloud-backup.update'), ['provider' => 'local', 'is_enabled' => '1']);

        $this->actingAs($user)
            ->from(route('dashboard.cloud-backup.index'))
            ->post(route('dashboard.cloud-backup.run'))
            ->assertRedirect(route('dashboard.cloud-backup.index'))
            ->assertSessionHasNoErrors();

        $run = CloudBackupRun::query()->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame('success', $run->status);

        $this->actingAs($user)
            ->get(route('dashboard.cloud-backup.index'))
            ->assertOk()
            ->assertSee($run->file_name, false);
    }

    #[Test]
    public function a_remote_file_can_be_deleted(): void
    {
        $this->bindFakeTransport();
        $user = $this->admin('manage_cloud_backup');

        $this->actingAs($user)->post(route('dashboard.cloud-backup.update'), ['provider' => 'local', 'is_enabled' => '1']);
        $this->actingAs($user)->post(route('dashboard.cloud-backup.run'));

        $file = CloudBackupRun::query()->latest('id')->first()->file_name;

        $this->actingAs($user)
            ->from(route('dashboard.cloud-backup.index'))
            ->delete(route('dashboard.cloud-backup.destroy', ['file' => $file]))
            ->assertRedirect(route('dashboard.cloud-backup.index'));

        $this->assertFileDoesNotExist(storage_path('app/backups/cloud/eskoofy-backups/'.$file));
    }

    #[Test]
    public function restoring_requires_the_restore_permission(): void
    {
        $this->bindFakeTransport();
        $user = $this->admin('manage_cloud_backup');

        $this->actingAs($user)
            ->post(route('dashboard.cloud-backup.restore', ['file' => 'anything.zip']))
            ->assertForbidden();
    }

    #[Test]
    public function restoring_requires_both_permissions_not_just_one(): void
    {
        $this->bindFakeTransport();

        // Either ability on its own must be refused: managing credentials is not
        // authority to overwrite the database, and being able to restore is not
        // authority to reach the provider settings.
        $this->actingAs($this->admin('manage_cloud_backup'))
            ->post(route('dashboard.cloud-backup.restore', ['file' => 'anything.zip']))
            ->assertForbidden();

        $this->actingAs($this->admin('restore_database'))
            ->post(route('dashboard.cloud-backup.restore', ['file' => 'anything.zip']))
            ->assertForbidden();
    }

    #[Test]
    public function the_settings_page_explains_an_unconfigured_provider(): void
    {
        $this->bindFakeTransport();

        $this->actingAs($this->admin('manage_cloud_backup'))->post(route('dashboard.cloud-backup.update'), [
            'provider' => 'dropbox',
        ]);

        $this->actingAs($this->admin('manage_cloud_backup'))
            ->get(route('dashboard.cloud-backup.index'))
            ->assertOk()
            ->assertSee('no complete credential set');
    }
}
