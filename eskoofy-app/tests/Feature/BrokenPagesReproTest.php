<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WebsiteMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrokenPagesReproTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin', 'web');
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        foreach ([
            'manage_chart_of_accounts',
            'manage_expenses',
            'view_financial_reports',
            'manage_media',
            'backup_database',
            'restore_database',
            'view_audit_log',
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    public function test_ledger_pages_render_with_realistic_data(): void
    {
        $cash = ChartOfAccount::create(['code' => '1000', 'name_en' => 'Cash', 'name_bn' => null, 'type' => 'asset', 'is_active' => true]);
        $bank = ChartOfAccount::create(['code' => '1010', 'name_en' => 'Bank', 'name_bn' => null, 'type' => 'asset', 'is_active' => true]);
        $income = ChartOfAccount::create(['code' => '4000', 'name_en' => 'Tuition', 'name_bn' => null, 'type' => 'income', 'is_active' => true]);
        ChartOfAccount::create(['code' => '5000', 'name_en' => 'Salary', 'name_bn' => null, 'type' => 'expense', 'is_active' => true]);

        LedgerEntry::create(['chart_of_account_id' => $cash->id, 'date' => now()->subDay(), 'debit' => 500, 'credit' => 0, 'note' => 'fee']);
        LedgerEntry::create(['chart_of_account_id' => $bank->id, 'date' => now(), 'debit' => 0, 'credit' => 500, 'note' => 'transfer']);
        LedgerEntry::create(['chart_of_account_id' => $income->id, 'date' => now(), 'debit' => 0, 'credit' => 500, 'note' => 'income']);

        $user = $this->adminUser();

        $this->actingAs($user)->get(route('dashboard.ledger.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.journal'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.cashbook'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.bankbook'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.reports.income-statement'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.reports.balance-sheet'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.reports.cash-flow'))->assertStatus(200);
    }

    public function test_media_pages_and_picker_render_with_images(): void
    {
        Storage::fake('public');
        $media = WebsiteMedia::create([
            'title' => 'Sample photo',
            'category' => 'Events',
            'file_path' => 'media/sample.png',
            'mime_type' => 'image/png',
            'file_size' => 1024,
        ]);
        Storage::disk('public')->put('media/sample.png', 'fake-png-bytes');

        $user = $this->adminUser();

        $this->actingAs($user)->get(route('dashboard.media.index'))->assertStatus(200)->assertSee('Sample photo');
        $this->actingAs($user)->get(route('dashboard.media.index', ['select' => '1']))->assertStatus(200)->assertSee('Sample photo');

        $response = $this->actingAs($user)->get(route('dashboard.media.download', $media));
        $response->assertStatus(200);
    }

    public function test_media_upload_stores_and_displays_image_url(): void
    {
        Storage::fake('public');
        $user = $this->adminUser();

        $file = UploadedFile::fake()->image('hero.png', 100, 100);
        $this->actingAs($user)->post(route('dashboard.media.store'), [
            'file' => $file,
            'title' => 'Hero',
            'category' => 'Homepage',
        ])->assertRedirect();

        $row = WebsiteMedia::first();
        $this->assertNotNull($row);
        $this->assertTrue($row->isImage());
        $this->assertStringStartsWith('media/', $row->file_path);
        $response = $this->actingAs($user)->get(route('dashboard.media.index'));
        $response->assertStatus(200);
    }

    public function test_announcements_pages_render(): void
    {
        $a = Announcement::create([
            'title' => 'Holiday notice',
            'audience' => ['all'],
            'display_target' => 'header',
            'is_published' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        $user = $this->adminUser();

        $this->actingAs($user)->get(route('dashboard.announcements.index'))->assertStatus(200)->assertSee('Holiday notice');
        $this->actingAs($user)->get(route('dashboard.announcements.create'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.announcements.edit', $a))->assertStatus(200);
    }

    public function test_help_and_activity_pages_render(): void
    {
        $user = $this->adminUser();

        activity('admin_actions')->causedBy($user)->log('Ran a report');

        $this->actingAs($user)->get(route('dashboard.help'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.activity.index'))->assertStatus(200)->assertSee('Ran a report', false);
    }

    public function test_backup_page_lists_real_local_disk_files(): void
    {
        Storage::disk('local')->put('backups/real_backup_20260828.zip', 'zip');
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('dashboard.backup.index'));
        $response->assertStatus(200);
        $response->assertSee('real_backup_20260828.zip', false);
    }

    public function test_site_and_dashboard_search_work(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->get(route('dashboard.search').'?q=zz')->assertStatus(200);
        $this->actingAs($user)->getJson('/dashboard/search?q=zz')->assertStatus(200)->assertJsonStructure(['data']);

        $this->get(route('site.search').'?q=hello')->assertStatus(200);
    }
}
