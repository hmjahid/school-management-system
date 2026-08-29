<?php

namespace Tests\Unit\Models;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerEntryTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): ChartOfAccount
    {
        return ChartOfAccount::create([
            'code' => 'CODE'.uniqid(),
            'name_en' => 'Ledger Account',
            'type' => ChartOfAccount::TYPE_EXPENSE,
        ]);
    }

    private function makeEntry(array $overrides = []): LedgerEntry
    {
        return LedgerEntry::create(array_merge([
            'chart_of_account_id' => $this->makeAccount()->id,
            'date' => now(),
            'debit' => 100.00,
            'credit' => 0.00,
        ], $overrides));
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $entry = $this->makeEntry();

        $this->assertDatabaseHas('ledger_entries', [
            'id' => $entry->id,
            'debit' => 100.00,
            'credit' => 0.00,
        ]);
        $this->assertEquals(100.0, (float) $entry->debit);
        $this->assertEquals(0.0, (float) $entry->credit);
    }

    /** @test */
    public function debit_and_credit_default_to_zero(): void
    {
        $entry = LedgerEntry::create([
            'chart_of_account_id' => $this->makeAccount()->id,
            'date' => now(),
        ]);

        $this->assertEquals(0.0, (float) $entry->debit);
        $this->assertEquals(0.0, (float) $entry->credit);
    }

    /** @test */
    public function it_casts_date_to_carbon(): void
    {
        $date = now()->subDays(4)->startOfDay();
        $entry = LedgerEntry::create([
            'chart_of_account_id' => $this->makeAccount()->id,
            'date' => $date,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $entry->date);
        $this->assertEquals($date->toDateString(), $entry->date->toDateString());
    }

    /** @test */
    public function it_belongs_to_a_chart_of_account(): void
    {
        $account = $this->makeAccount();
        $entry = LedgerEntry::create([
            'chart_of_account_id' => $account->id,
            'date' => now(),
        ]);

        $this->assertInstanceOf(ChartOfAccount::class, $entry->account);
        $this->assertEquals($account->id, $entry->account->id);
    }

    /** @test */
    public function it_has_a_polymorphic_reference(): void
    {
        $entry = $this->makeEntry(['reference_type' => 'expense', 'reference_id' => 42]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $entry->reference());
        $this->assertEquals('expense', $entry->reference_type);
        $this->assertEquals(42, $entry->reference_id);
    }
}
