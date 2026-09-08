<?php

namespace Tests\Unit\Models;

use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): ChartOfAccount
    {
        return ChartOfAccount::create([
            'code' => 'CODE'.uniqid(),
            'name_en' => 'Office',
            'type' => ChartOfAccount::TYPE_EXPENSE,
        ]);
    }

    private function makeExpense(array $overrides = []): Expense
    {
        return Expense::create(array_merge([
            'category' => 'Utilities',
            'amount' => 1500.75,
            'date' => now()->subDays(2),
            'vendor' => 'Acme',
            'payment_method' => 'cash',
        ], $overrides));
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $expense = $this->makeExpense();

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category' => 'Utilities',
            'amount' => 1500.75,
        ]);
        $this->assertEquals(1500.75, (float) $expense->amount);
    }

    #[Test]
    public function it_casts_date_to_carbon(): void
    {
        $date = now()->subWeek()->startOfDay();
        $expense = Expense::create([
            'category' => 'Travel',
            'amount' => 100,
            'date' => $date,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $expense->date);
        $this->assertEquals($date->toDateString(), $expense->date->toDateString());
    }

    #[Test]
    public function it_can_be_queried_by_date_with_where_date(): void
    {
        $target = now()->subDays(3)->startOfDay();
        $this->makeExpense(['date' => $target]);
        $this->makeExpense(['date' => now()->startOfDay()]);

        $found = Expense::whereDate('date', $target)->get();

        $this->assertCount(1, $found);
    }

    #[Test]
    public function it_belongs_to_an_expense_category(): void
    {
        $category = ExpenseCategory::create(['name' => 'Cat '.uniqid()]);
        $expense = $this->makeExpense();
        $expense->forceFill(['expense_category_id' => $category->id])->save();

        $this->assertInstanceOf(ExpenseCategory::class, $expense->category()->first());
        $this->assertEquals($category->id, $expense->category()->first()->id);
    }

    #[Test]
    public function it_belongs_to_a_chart_of_account(): void
    {
        $account = $this->makeAccount();
        $expense = Expense::create([
            'category' => 'Misc',
            'amount' => 50,
            'date' => now(),
            'chart_of_account_id' => $account->id,
        ]);

        $this->assertInstanceOf(ChartOfAccount::class, $expense->account);
        $this->assertEquals($account->id, $expense->account->id);
    }

    #[Test]
    public function it_belongs_to_a_creator(): void
    {
        $user = User::factory()->create();
        $expense = Expense::create([
            'category' => 'Misc',
            'amount' => 50,
            'date' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $expense->creator);
        $this->assertEquals($user->id, $expense->creator->id);
    }

    #[Test]
    public function category_is_nullable_when_expense_category_absent(): void
    {
        $expense = $this->makeExpense();

        $this->assertNull($expense->category()->first());
        $this->assertEquals('Utilities', $expense->getAttribute('category'));
    }
}
