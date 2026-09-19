<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WebsiteMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrokenPagesEdgeCaseTest extends TestCase
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

    public function test_ledger_page_with_reference_to_missing_models(): void
    {
        $cash = ChartOfAccount::create(['code' => '1000', 'name_en' => 'Cash', 'name_bn' => null, 'type' => 'asset', 'is_active' => true]);

        LedgerEntry::create(['chart_of_account_id' => $cash->id, 'date' => now(), 'debit' => 10, 'credit' => 0, 'reference_type' => 'expense', 'reference_id' => 99999, 'note' => 'orphan']);
        LedgerEntry::create(['chart_of_account_id' => $cash->id, 'date' => now(), 'debit' => 10, 'credit' => 0, 'reference_type' => 'fee_payment', 'reference_id' => 99999, 'note' => 'orphan']);
        LedgerEntry::create(['chart_of_account_id' => $cash->id, 'date' => now()->subDay(), 'debit' => 0, 'credit' => 5, 'note' => 'plain']);

        $user = $this->adminUser();

        $this->actingAs($user)->get(route('dashboard.ledger.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.cashbook'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.bankbook'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.reports.cash-flow'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.reports.income-statement'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.reports.balance-sheet'))->assertStatus(200);
    }

    public function test_ledger_requiring_expense_only_user(): void
    {
        $cash = ChartOfAccount::create(['code' => '1000', 'name_en' => 'Cash', 'name_bn' => null, 'type' => 'asset', 'is_active' => true]);

        $user = User::factory()->create();
        Role::findOrCreate('accountant', 'web');
        $user->assignRole('accountant');
        Permission::findOrCreate('manage_expenses', 'web');
        $user->givePermissionTo('manage_expenses');

        $this->actingAs($user)->get(route('dashboard.ledger.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.cashbook'))->assertStatus(200);
    }

    public function test_activity_page_with_deleted_causer_and_legacy_rows(): void
    {
        $user = $this->adminUser();

        activity('legacy')->log('Old entry without causer');
        $deleted = User::factory()->create();
        activity('users')->causedBy($deleted)->log('Made by a now-deleted user');
        $deleted->delete();

        $this->actingAs($user)->get(route('dashboard.activity.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.activity.index').'?log_name=users')->assertStatus(200);
        $this->assertSame(1, Activity::query()->where('description', 'Made by a now-deleted user')->count());
    }

    public function test_critical_actions_log_activity(): void
    {
        $user = $this->adminUser();

        Expense::create(['category' => 'Stationery', 'amount' => 250.50, 'date' => now(), 'payment_method' => 'cash', 'created_by' => $user->id]);
        Announcement::create(['title' => 'Holiday', 'audience' => ['all'], 'display_target' => 'header', 'is_published' => true]);

        $this->actingAs($user)->get(route('dashboard.activity.index'))->assertStatus(200);
        $this->assertSame(1, Activity::query()->where('log_name', 'expenses')->count());
        $this->assertSame(1, Activity::query()->where('log_name', 'announcements')->count());
        $this->assertSame('expenses', Activity::query()->where('log_name', 'expenses')->value('log_name'));
    }

    public function test_media_legacy_mime_rows_still_render_image(): void
    {
        $user = $this->adminUser();
        WebsiteMedia::create([
            'title' => 'Legacy JPEG',
            'file_path' => 'media/old-photo.jpg',
            'mime_type' => 'application/octet-stream',
            'file_size' => 500,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.media.index'));
        $response->assertStatus(200);
    }

    public function test_announcement_string_audience_legacy_row(): void
    {
        $user = $this->adminUser();
        $a = Announcement::create([
            'title' => 'Old row',
            'audience' => ['all'],
            'display_target' => 'header',
            'is_published' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard.announcements.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.announcements.edit', $a))->assertStatus(200);
    }

    public function test_backup_page_when_backup_directory_missing(): void
    {
        \Illuminate\Support\Facades\Storage::disk('local')->deleteDirectory('backups');
        $user = $this->adminUser();

        $this->actingAs($user)->get(route('dashboard.backup.index'))->assertStatus(200);
    }

    public function test_not_found_page_renders_without_error(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertStatus(404)
            ->assertSee('Page Not Found', false);
    }
}
