<?php

namespace Tests\Unit\Models;

use App\Models\Budget;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): ExpenseCategory
    {
        return ExpenseCategory::create(['name' => 'Cat '.uniqid()]);
    }

    private function makeBudget(array $overrides = []): Budget
    {
        return Budget::create(array_merge([
            'expense_category_id' => $this->makeCategory()->id,
            'period_type' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'amount' => 5000.00,
        ], $overrides));
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $budget = $this->makeBudget();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'period_type' => 'monthly',
            'amount' => 5000.00,
        ]);
        $this->assertEquals(5000.0, (float) $budget->amount);
    }

    #[Test]
    public function it_casts_period_dates_to_carbon(): void
    {
        $start = now()->startOfMonth()->startOfDay();
        $end = now()->endOfMonth()->startOfDay();
        $budget = Budget::create([
            'expense_category_id' => $this->makeCategory()->id,
            'period_start' => $start,
            'period_end' => $end,
            'amount' => 100,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $budget->period_start);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $budget->period_end);
        $this->assertEquals($start->toDateString(), $budget->period_start->toDateString());
    }

    #[Test]
    public function it_defaults_period_type_to_monthly(): void
    {
        $budget = Budget::create([
            'expense_category_id' => $this->makeCategory()->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'amount' => 100,
        ]);

        $this->assertEquals('monthly', $budget->fresh()->period_type);
    }

    #[Test]
    public function it_belongs_to_an_expense_category(): void
    {
        $category = $this->makeCategory();
        $budget = Budget::create([
            'expense_category_id' => $category->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'amount' => 100,
        ]);

        $this->assertInstanceOf(ExpenseCategory::class, $budget->category);
        $this->assertEquals($category->id, $budget->category->id);
    }

    #[Test]
    public function expense_category_is_nullable(): void
    {
        $budget = Budget::create([
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'amount' => 100,
        ]);

        $this->assertNull($budget->category);
    }
}
