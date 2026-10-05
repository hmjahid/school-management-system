<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ProductRecommender;
use App\Services\VariantResolver;
use Tests\TestCase;

class ProductRecommenderTest extends TestCase
{
    private function answers(array $overrides = []): array
    {
        return array_merge([
            'market'   => 'int',
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

    // ------------------------------------------------------- the variant axis

    public function test_it_returns_a_valid_variant(): void
    {
        $result = ProductRecommender::recommend($this->answers(['market' => 'bd']));

        $this->assertSame(VariantResolver::BD, $result['variant']);
    }

    /**
     * The market answer is a separate axis from the product answer, so it must
     * not change *which* product wins — otherwise a Bangladeshi school on
     * shared hosting would be told to buy a different product.
     */
    public function test_market_does_not_change_the_recommended_product(): void
    {
        $bd = ProductRecommender::recommend($this->answers(['market' => 'bd']));
        $int = ProductRecommender::recommend($this->answers(['market' => 'int']));

        $this->assertSame($int['product'], $bd['product']);
        $this->assertSame($int['score'], $bd['score']);
        $this->assertNotSame($bd['variant'], $int['variant']);
    }

    public function test_missing_or_invalid_market_falls_back_to_int(): void
    {
        foreach ([null, '', 'nope', 'BD-ISH'] as $market) {
            $result = ProductRecommender::recommend($this->answers(['market' => $market]));

            $this->assertSame(VariantResolver::INT, $result['variant'], var_export($market, true));
        }
    }

    public function test_reason_key_reflects_the_market_when_no_product_signal_applies(): void
    {
        $this->assertSame(
            'choose.reason.bd',
            ProductRecommender::recommend($this->answers([
                'hosting' => '', 'stack' => 'any', 'priority' => '', 'market' => 'bd',
            ]))['reason_key'],
        );

        $this->assertSame(
            'choose.reason.int',
            ProductRecommender::recommend($this->answers([
                'hosting' => '', 'stack' => 'any', 'priority' => '', 'market' => 'int',
            ]))['reason_key'],
        );
    }

    /**
     * The result maps onto exactly one cell of the Product x Variant matrix, so
     * a product/variant pair that no product page can render would strand the
     * visitor on the choose-result page.
     */
    public function test_result_is_a_renderable_matrix_cell(): void
    {
        foreach (['bd', 'int'] as $market) {
            foreach (['shared', 'vps', 'cloud', 'wordpress'] as $hosting) {
                $result = ProductRecommender::recommend($this->answers([
                    'market' => $market,
                    'hosting' => $hosting,
                ]));

                $this->assertContains($result['product'], ProductRecommender::productKeys());
                $this->assertContains($result['variant'], VariantResolver::all());
            }
        }
    }

    public function test_candidates_are_products_not_variants(): void
    {
        foreach (ProductRecommender::candidates() as $candidate) {
            $this->assertContains($candidate['key'], ProductRecommender::productKeys());
            $this->assertNotContains($candidate['key'], VariantResolver::all());
        }
    }
}