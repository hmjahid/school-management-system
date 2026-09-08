<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackupPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_page_lists_backups_from_local_disk(): void
    {
        Storage::disk('local')->put('backups/fake_backup_1.zip', 'zipdata');
        Storage::disk('local')->put('backups/not_a_backup.txt', 'no');

        Role::create(['name' => 'admin']);
        $perm = Permission::findOrCreate('backup_database', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo($perm);

        $response = $this->actingAs($user)->get(route('dashboard.backup.index'));
        $response->assertStatus(200);
        $response->assertSee('fake_backup_1.zip', false);
        $response->assertDontSee('not_a_backup.txt', false);
    }

    public function test_backup_command_writes_to_local_disk_backups_dir(): void
    {
        Storage::fake('local');
        $this->artisan('backup:run')->assertExitCode(0);
        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);
        $this->assertStringEndsWith('.zip', $files[0]);
    }
}
