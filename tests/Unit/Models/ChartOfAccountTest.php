<?php

namespace Tests\Unit\Models;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(array $overrides = []): ChartOfAccount
    {
        return ChartOfAccount::create(array_merge([
            'code' => 'CODE'.uniqid(),
            'name_en' => 'Account',
            'type' => ChartOfAccount::TYPE_EXPENSE,
        ], $overrides));
    }

    /** @test */
    public function it_exposes_type_constants(): void
    {
        $this->assertEquals('asset', ChartOfAccount::TYPE_ASSET);
        $this->assertEquals('liability', ChartOfAccount::TYPE_LIABILITY);
        $this->assertEquals('income', ChartOfAccount::TYPE_INCOME);
        $this->assertEquals('expense', ChartOfAccount::TYPE_EXPENSE);
        $this->assertEquals('equity', ChartOfAccount::TYPE_EQUITY);
        $this->assertEquals('bank', ChartOfAccount::TYPE_BANK);
    }

    /** @test */
    public function it_persists_key_columns_and_defaults_is_active(): void
    {
        $account = $this->makeAccount();

        $this->assertDatabaseHas('chart_of_accounts', [
            'id' => $account->id,
            'code' => $account->code,
            'type' => 'expense',
        ]);
        $this->assertTrue($account->fresh()->is_active);
    }

    /** @test */
    public function it_belongs_to_a_parent_and_has_children(): void
    {
        $parent = $this->makeAccount(['name_en' => 'Parent', 'type' => ChartOfAccount::TYPE_ASSET]);
        $child = $this->makeAccount([
            'name_en' => 'Child',
            'type' => ChartOfAccount::TYPE_ASSET,
            'parent_id' => $parent->id,
        ]);

        $this->assertInstanceOf(ChartOfAccount::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertCount(1, $parent->children);
        $this->assertInstanceOf(ChartOfAccount::class, $parent->children->first());
    }

    /** @test */
    public function it_has_many_entries(): void
    {
        $account = $this->makeAccount();
        LedgerEntry::create(['chart_of_account_id' => $account->id, 'date' => now(), 'debit' => 100, 'credit' => 0]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $account->entries());
        $this->assertCount(1, $account->entries);
    }

    /** @test */
    public function balance_for_asset_is_debit_minus_credit(): void
    {
        $account = $this->makeAccount(['type' => ChartOfAccount::TYPE_ASSET]);
        LedgerEntry::create(['chart_of_account_id' => $account->id, 'date' => now(), 'debit' => 500, 'credit' => 200]);
        LedgerEntry::create(['chart_of_account_id' => $account->id, 'date' => now(), 'debit' => 100, 'credit' => 50]);

        $this->assertEquals(350.0, $account->balance());
    }

    /** @test */
    public function balance_for_income_is_credit_minus_debit(): void
    {
        $account = $this->makeAccount(['type' => ChartOfAccount::TYPE_INCOME]);
        LedgerEntry::create(['chart_of_account_id' => $account->id, 'date' => now(), 'debit' => 100, 'credit' => 400]);

        $this->assertEquals(300.0, $account->balance());
    }

    /** @test */
    public function balance_respects_date_range(): void
    {
        $account = $this->makeAccount(['type' => ChartOfAccount::TYPE_ASSET]);
        LedgerEntry::create(['chart_of_account_id' => $account->id, 'date' => now()->subMonth(), 'debit' => 1000, 'credit' => 0]);
        LedgerEntry::create(['chart_of_account_id' => $account->id, 'date' => now(), 'debit' => 100, 'credit' => 0]);

        $this->assertEquals(100.0, $account->balance(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()));
    }
}
