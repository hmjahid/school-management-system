<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ProductRecommender;
use Tests\TestCase;

class ProductRecommenderTest extends TestCase
{
    private function answers(array $overrides = []): array
    {
        return array_merge([
            'size'     => 'medium',
            'comfort'  => 'some',
            'hosting'  => 'vps',
            'stack'    => 'any',
            'priority' => 'features',
        ], $overrides);
    }

    public function test_default_preference_leads_to_app(): void
    {
        $result = ProductRecommender::recommend($this->answers());

        $this->assertSame('app', $result['product']);
    }

    public function test_wordpress_hosting_leads_to_theme(): void
    {
        $result = ProductRecommender::recommend($this->answers([
            'hosting' => 'wordpress',
        ]));

        $this->assertSame('theme', $result['product']);
    }

    public function test_shared_hosting_with_budget_leads_to_php(): void
    {
        $result = ProductRecommender::recommend($this->answers([
            'hosting' => 'shared',
            'priority' => 'budget',
            'comfort' => 'technical',
        ]));

        $this->assertSame('php', $result['product']);
    }

    public function test_javascript_stack_leads_to_node(): void
    {
        $result = ProductRecommender::recommend($this->answers([
            'stack' => 'javascript',
            'comfort' => 'technical',
        ]));

        $this->assertSame('node', $result['product']);
    }

    public function test_feature_priority_outranks_budget_when_on_vps(): void
    {
        // Feature priority gives app a strong lead even with technical comfort
        // that would otherwise favour php/node.
        $result = ProductRecommender::recommend($this->answers([
            'comfort' => 'technical',
        ]));

        $this->assertSame('app', $result['product']);
        $this->assertTrue($result['score']['app'] >= $result['score']['php']);
    }

    public function test_recommendation_is_deterministic(): void
    {
        $a = ProductRecommender::recommend($this->answers(['hosting' => 'shared']));
        $b = ProductRecommender::recommend($this->answers(['hosting' => 'shared']));

        $this->assertSame($a['product'], $b['product']);
        $this->assertSame($a['runnerUp'], $b['runnerUp']);
    }

    public function test_reason_key_is_returned(): void
    {
        $result = ProductRecommender::recommend($this->answers(['hosting' => 'wordpress']));

        $this->assertSame('choose.reason.wordpress', $result['reason_key']);
    }

    public function test_candidates_cover_all_four_products(): void
    {
        $keys = ProductRecommender::productKeys();

        $this->assertSame(['app', 'php', 'theme', 'node'], $keys);
    }

    public function test_runner_up_differs_from_winner(): void
    {
        $result = ProductRecommender::recommend($this->answers());

        $this->assertNotSame($result['product'], $result['runnerUp']);
    }
}