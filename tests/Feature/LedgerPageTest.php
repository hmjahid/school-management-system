<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LedgerPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin', 'web');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        foreach (['manage_chart_of_accounts', 'manage_expenses'] as $perm) {
            Permission::findOrCreate($perm, 'web');
            $user->givePermissionTo($perm);
        }
        return $user;
    }

    public function test_ledger_pages_load(): void
    {
        $cash = ChartOfAccount::create(['code' => '1000', 'name_en' => 'Cash', 'name_bn' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $bank = ChartOfAccount::create(['code' => '1010', 'name_en' => 'Bank', 'name_bn' => 'Bank', 'type' => 'asset', 'is_active' => true]);

        \App\Models\LedgerEntry::create(['chart_of_account_id' => $cash->id, 'date' => now(), 'debit' => 100, 'credit' => 0, 'note' => 'test']);
        \App\Models\LedgerEntry::create(['chart_of_account_id' => $bank->id, 'date' => now(), 'debit' => 0, 'credit' => 50, 'note' => 'test']);

        $user = $this->admin();

        $this->actingAs($user)->get(route('dashboard.ledger.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.cashbook'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.bankbook'))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard.ledger.journal'))->assertStatus(200);
    }
}
