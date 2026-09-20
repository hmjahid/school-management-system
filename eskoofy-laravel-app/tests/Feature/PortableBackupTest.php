<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\PortableBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class PortableBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_backup_run_embeds_portable_manifest_and_tables(): void
    {
        $class = SchoolClass::create(['name' => 'Class 5', 'code' => 'C1']);
        $user = User::factory()->create();
        Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'admission_number' => 'ADM-2024-0001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'admission_date' => '2024-01-15',
        ]);

        $this->artisan('backup:run')->assertExitCode(0);

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);

        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($files[0]));

        $this->assertNotFalse($zip->getFromName(PortableBackupService::MANIFEST_FILE));
        $manifest = json_decode($zip->getFromName(PortableBackupService::MANIFEST_FILE), true);
        $this->assertSame(PortableBackupService::FORMAT, $manifest['format']);
        $this->assertSame(1, $manifest['version']);
        $this->assertSame('laravel', $manifest['variant']);

        $tables = json_decode($zip->getFromName(PortableBackupService::TABLES_FILE), true)['tables'];
        $section = collect($tables)->firstWhere('table', 'school_classes');
        $this->assertSame(['C1'], array_column($section['rows'], array_search('code', $section['columns'], true)));
    }

    public function test_backup_restore_rebuilds_deleted_rows_from_the_portable_dump(): void
    {
        $class = SchoolClass::create(['name' => 'Class 5', 'code' => 'C1']);
        $user = User::factory()->create();
        Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'admission_number' => 'ADM-2024-0001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'admission_date' => '2024-01-15',
        ]);

        $this->artisan('backup:run')->assertExitCode(0);
        $file = Storage::disk('local')->files('backups')[0];

        // Simulate data loss after the backup was taken.
        Student::query()->delete();
        User::query()->delete();
        SchoolClass::query()->delete();
        $this->assertSame(0, Student::count());

        $this->artisan('backup:restore', ['file' => basename($file), '--force' => true])->assertExitCode(0);

        $this->assertSame(1, Student::count());
        $this->assertSame('ADM-2024-0001', Student::first()->admission_number);
        $this->assertSame('C1', SchoolClass::first()->code);
        $this->assertSame(1, User::count());
        $this->assertFalse(User::first()->password === 'password');
    }

    public function test_backup_restore_extracts_uploaded_storage_files(): void
    {
        $public = storage_path('app/public');
        File::ensureDirectoryExists($public.'/uploads');
        File::put($public.'/uploads/note.txt', 'file-content');

        $this->artisan('backup:run')->assertExitCode(0);
        $file = Storage::disk('local')->files('backups')[0];

        File::delete($public.'/uploads/note.txt');

        $this->artisan('backup:restore', ['file' => basename($file), '--force' => true])->assertExitCode(0);

        $this->assertSame('file-content', File::get($public.'/uploads/note.txt'));

        File::deleteDirectory($public);
    }

    public function test_restore_rejects_a_non_portable_archive(): void
    {
        $filename = 'backup_20240101_101010_abcdef.zip';
        Storage::disk('local')->put('backups/'.$filename, 'not a zip');

        $this->artisan('backup:restore', ['file' => $filename, '--force' => true])
            ->expectsOutputToContain('Failed to open zip.')
            ->assertExitCode(1);
    }

    public function test_portable_dump_matches_the_node_variant_layout(): void
    {
        $user = User::factory()->create(['email' => 'jane@eskoofy.test', 'name' => 'Jane']);
        $dump = app(PortableBackupService::class)->dump();
        $this->assertSame('eskoofy-portable-backup', $dump['manifest']['format']);
        $this->assertSame(1, $dump['manifest']['version']);
        $this->assertArrayHasKey('engine', $dump['manifest']);
        $this->assertSame('laravel', $dump['manifest']['variant']);

        $users = collect($dump['tables']['tables'])->firstWhere('table', 'users');
        $index = array_search('email', $users['columns'], true);
        $emails = array_column($users['rows'], $index);
        $this->assertContains('jane@eskoofy.test', $emails);
    }
}
