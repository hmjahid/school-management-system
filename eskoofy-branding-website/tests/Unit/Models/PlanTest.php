<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Core\Model;
use App\Models\Plan;
use Tests\FakeDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new FakeDatabase();
        Model::setTestDatabase($this->db);
    }

    protected function tearDown(): void
    {
        Model::setTestDatabase(null);
        parent::tearDown();
    }

    private function seedPlan(string $product, array $overrides = []): int
    {
        return $this->db->insert('plans', array_merge([
            'product'         => $product,
            'name'            => ucfirst($product) . ' plan',
            'slug'            => $product . '-plan',
            'description'     => $product . ' plan',
            'price'           => 9.0,
            'currency'        => 'USD',
            'period'          => 'monthly',
            'max_activations' => 3,
            'active'          => 1,
            'sort_order'      => 1,
        ], $overrides));
    }

    public function test_active_for_returns_only_active_plans_for_product(): void
    {
        $this->seedPlan('php', ['slug' => 'php-1', 'sort_order' => 1]);
        $this->seedPlan('php', ['slug' => 'php-2', 'sort_order' => 2]);
        $this->seedPlan('app', ['slug' => 'app-1']);
        $this->seedPlan('php', ['slug' => 'php-3', 'active' => 0]);

        $rows = Plan::activeFor('php');

        $this->assertCount(2, $rows);
        $this->assertSame('php-1', $rows[0]['slug']);
        $this->assertSame('php-2', $rows[1]['slug']);
    }

    public function test_active_for_returns_empty_for_unknown_product(): void
    {
        $this->seedPlan('php');

        $this->assertSame([], Plan::activeFor('theme'));
    }
}