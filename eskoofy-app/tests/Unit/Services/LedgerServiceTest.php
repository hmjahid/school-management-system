<?php

namespace Tests\Unit\Services;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_posts_a_single_entry(): void
    {
        $account = ChartOfAccount::create([
            'code' => '1000',
            'name_en' => 'Cash',
            'type' => 'asset',
        ]);

        $service = app(LedgerService::class);
        $entry = $service->postEntry($account->id, 500.00, 0.00);

        $this->assertInstanceOf(LedgerEntry::class, $entry);
        $this->assertEquals(500.00, (float) $entry->debit);
        $this->assertEquals(0.00, (float) $entry->credit);
        $this->assertEquals($account->id, $entry->chart_of_account_id);
    }

    #[Test]
    public function it_defaults_date_to_today_when_not_provided(): void
    {
        $account = ChartOfAccount::create([
            'code' => '1001',
            'name_en' => 'Bank',
            'type' => 'asset',
        ]);

        $service = app(LedgerService::class);
        $entry = $service->postEntry($account->id, 100.00, 0.00);

        $this->assertEquals(now()->toDateString(), $entry->date->toDateString());
    }

    #[Test]
    public function it_posts_a_balanced_journal_entry(): void
    {
        $cash = ChartOfAccount::create(['code' => '1002', 'name_en' => 'Cash', 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4000', 'name_en' => 'Revenue', 'type' => 'income']);

        $service = app(LedgerService::class);
        $entries = $service->postJournal([
            ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000],
        ]);

        $this->assertCount(2, $entries);
        $this->assertDatabaseCount('ledger_entries', 2);
    }

    #[Test]
    public function it_throws_for_unbalanced_journal_entry(): void
    {
        $cash = ChartOfAccount::create(['code' => '1003', 'name_en' => 'Cash', 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4001', 'name_en' => 'Revenue', 'type' => 'income']);

        $service = app(LedgerService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry unbalanced');

        $service->postJournal([
            ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
        ]);
    }

    #[Test]
    public function it_allows_tiny_rounding_difference_within_tolerance(): void
    {
        $cash = ChartOfAccount::create(['code' => '1004', 'name_en' => 'Cash', 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4002', 'name_en' => 'Revenue', 'type' => 'income']);

        $service = app(LedgerService::class);

        // Difference of 0.001 should be within the 0.001 tolerance
        $entries = $service->postJournal([
            ['account_id' => $cash->id, 'debit' => 1000.001, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000.000],
        ]);

        $this->assertCount(2, $entries);
    }

    #[Test]
    public function it_finds_account_by_code(): void
    {
        $account = ChartOfAccount::create([
            'code' => '5000',
            'name_en' => 'Expense',
            'type' => 'expense',
        ]);

        $service = app(LedgerService::class);
        $found = $service->findAccountByCode('5000');

        $this->assertNotNull($found);
        $this->assertEquals($account->id, $found->id);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_account_code(): void
    {
        $service = app(LedgerService::class);
        $found = $service->findAccountByCode('NONEXIST');

        $this->assertNull($found);
    }

    #[Test]
    public function it_stores_reference_for_polymorphic_link(): void
    {
        $account = ChartOfAccount::create(['code' => '1005', 'name_en' => 'Cash', 'type' => 'asset']);

        $reference = ChartOfAccount::create(['code' => '9999', 'name_en' => 'Ref', 'type' => 'asset']);

        $service = app(LedgerService::class);
        $entry = $service->postEntry($account->id, 200, 0, null, $reference, 'Linked entry');

        $this->assertEquals(ChartOfAccount::class, $entry->reference_type);
        $this->assertEquals($reference->id, $entry->reference_id);
        $this->assertEquals('Linked entry', $entry->note);
    }
}
