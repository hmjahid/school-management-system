<?php

namespace Tests\Unit\Models;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(array $overrides = []): ExpenseCategory
    {
        return ExpenseCategory::create(array_merge([
            'name' => 'Category '.uniqid(),
            'color' => '#ff0000',
        ], $overrides));
    }

    /** @test */
    public function it_persists_name_and_is_active_defaults_to_true(): void
    {
        $category = $this->makeCategory();

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'name' => $category->name]);
        $this->assertTrue($category->fresh()->is_active);
    }

    /** @test */
    public function is_active_can_be_disabled(): void
    {
        $category = $this->makeCategory(['is_active' => false]);

        $this->assertFalse($category->is_active);
    }

    /** @test */
    public function it_has_many_expenses(): void
    {
        $category = $this->makeCategory();
        $a = Expense::create(['category' => 'A', 'amount' => 10, 'date' => now()]);
        $a->forceFill(['expense_category_id' => $category->id])->save();
        $b = Expense::create(['category' => 'B', 'amount' => 20, 'date' => now()]);
        $b->forceFill(['expense_category_id' => $category->id])->save();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $category->expenses());
        $this->assertCount(2, $category->fresh()->expenses);
    }

    /** @test */
    public function it_has_many_budgets(): void
    {
        $category = $this->makeCategory();
        Budget::create([
            'expense_category_id' => $category->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'amount' => 1000,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $category->budgets());
        $this->assertCount(1, $category->budgets);
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $category = $this->makeCategory();
        $id = $category->id;

        $category->delete();

        $this->assertSoftDeleted('expense_categories', ['id' => $id]);
        $this->assertNull(ExpenseCategory::find($id));
        $this->assertNotNull(ExpenseCategory::withTrashed()->find($id));
    }
}
