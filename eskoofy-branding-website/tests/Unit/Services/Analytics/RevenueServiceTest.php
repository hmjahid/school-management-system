<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Gateways\GatewayFactory;
use App\Services\Analytics\DateRange;
use App\Services\Analytics\RevenueService;
use Tests\Support\AggregateDatabaseStub;
use Tests\TestCase;

/**
 * Covers dense trend bucketing, MRR correctness, and the MRR-vs-collected
 * reconciliation.
 *
 * Bug references come from docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.3.
 */
class RevenueServiceTest extends TestCase
{
    private AggregateDatabaseStub $db;

    private RevenueService $service;

    /** Pinned "now". */
    private const NOW = '2026-10-04 12:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new AggregateDatabaseStub();
        $this->service = new RevenueService($this->db);
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC'));
    }

    private function range(string $preset = '12mo'): DateRange
    {
        return DateRange::fromRequest(['range' => $preset], $this->now());
    }

    // ------------------------------------------------------------- dense trend

    /**
     * B5: the old series was a straight `GROUP BY` on a month-anchored window, so
     * any month without sales disappeared from the chart entirely and the line
     * silently connected across the gap.
     */
    public function testTrendZeroFillsMonthsWithNoPayments(): void
    {
        $this->db->on('AS bucket', [
            ['bucket' => '2025-10', 'total' => 120],
            ['bucket' => '2026-03', 'total' => 90],
        ]);

        $trend = $this->service->trend($this->range('12mo'));

        self::assertCount(13, $trend);

        $keys   = array_column($trend, 'key');
        $values = array_column($trend, 'value');

        self::assertSame('2025-10', $keys[0]);
        self::assertSame('2026-10', $keys[12]);

        // The gap between Nov and Mar is filled with explicit zeros, not skipped.
        self::assertSame(0.0, $values[1]);
        self::assertSame(0.0, $values[2]);
        self::assertSame(0.0, $values[3]);
        self::assertSame(0.0, $values[4], 'Feb 2026 has no payments and must still be present');

        self::assertSame(120.0, $values[0]);
        self::assertSame(90.0, $values[4 + 1]);
    }

    public function testTrendAlwaysMatchesTheWindowLength(): void
    {
        $this->db->on('AS bucket', []);

        foreach (['7d' => 7, '30d' => 30, '12mo' => 13, 'ytd' => 10] as $preset => $expected) {
            self::assertCount($expected, $this->service->trend($this->range($preset)), $preset);
        }
    }

    public function testTrendKeysAreUniqueAndAscending(): void
    {
        $this->db->on('AS bucket', [
            ['bucket' => '2026-03', 'total' => 5],
            ['bucket' => '2026-01', 'total' => 5],
        ]);

        $keys = array_column($this->service->trend($this->range('12mo')), 'key');

        self::assertSame($keys, array_values(array_unique($keys)));
        $sorted = $keys;
        sort($sorted);
        self::assertSame($sorted, $keys);
    }

    public function testTrendKeysMatchWhatSqlWouldHaveGroupedOn(): void
    {
        $range = $this->range('12mo');
        $this->db->on('AS bucket', [['bucket' => '2026-03', 'total' => 1]]);

        $this->service->trend($range);

        // If the PHP key format and the SQL DATE_FORMAT ever diverge, every bucket
        // renders as zero — so pin the expression to the window's key format.
        self::assertStringContainsString(
            $range->keyFormat(),
            str_replace('%', '', $this->db->sql())
        );
    }

    public function testTrendRowsAreFilteredToPaidPaymentsOnly(): void
    {
        $this->db->on('AS bucket', []);

        $this->service->trend($this->range());

        $sql = $this->db->sql();
        self::assertStringContainsString("status = 'paid'", $sql);
        self::assertStringContainsString('paid_at IS NOT NULL', $sql);
    }

    public function testTrendCarriesNonZeroLabelsForTheAxis(): void
    {
        $this->db->on('AS bucket', []);

        foreach ($this->service->trend($this->range('12mo')) as $point) {
            self::assertArrayHasKey('label', $point);
            self::assertNotSame('', $point['label']);
        }
    }

    // ------------------------------------------------------------ product join

    /**
     * `payments` has no `product` column — it comes from the plan a payment
     * settles. A product filter must therefore add the join, or MySQL errors on
     * an unknown column.
     */
    public function testProductFilterAddsThePlansJoinAndQualifiesColumns(): void
    {
        $this->db->on('FROM payments p JOIN plans pl', []);

        $this->service->trend($this->range(), ['product' => 'node']);

        $sql = $this->db->sql();
        self::assertStringContainsString('JOIN plans pl ON pl.id = p.plan_id', $sql);
        self::assertStringContainsString('pl.product = ?', $sql);
        self::assertStringContainsString('p.status', $sql);
        self::assertContains('node', $this->db->queries[0]['params']);
    }

    public function testNoProductFilterMeansNoJoin(): void
    {
        $this->db->on('FROM payments', []);

        $this->service->trend($this->range());

        $sql = $this->db->sql();
        self::assertStringNotContainsString('JOIN plans', $sql);
        self::assertStringNotContainsString('pl.', $sql);
    }

    public function testVariantFilterWorksWithoutTheJoin(): void
    {
        $this->db->on('FROM payments', []);

        $this->service->trend($this->range(), ['variant' => 'bd']);

        self::assertStringContainsString('variant = ?', $this->db->sql());
        self::assertStringNotContainsString('JOIN plans', $this->db->sql());
    }

    public function testCombinedFiltersApplyBothTheJoinAndTheVariantPredicate(): void
    {
        $this->db->on('FROM payments p JOIN plans pl', []);

        $this->service->trend($this->range(), ['product' => 'node', 'variant' => 'bd']);

        $params = $this->db->queries[0]['params'];
        self::assertStringContainsString('p.variant = ?', $this->db->sql());
        self::assertContains('node', $params);
        self::assertContains('bd', $params);
    }

    /**
     * Regression guard: an early draft of byVariant()/byGateway() interpolated the
     * `$where` array straight into the SQL, emitting `WHERE Array`. That is a
     * syntax error in MySQL, so both panels fataled at runtime while every
     * aggregate-styled unit test still passed.
     */
    public function testNoQueryInterpolatesAPredicateArrayInsteadOfJoiningIt(): void
    {
        $this->db->on('GROUP BY', []);

        $this->service->byVariant($this->range());
        $this->service->byGateway($this->range());
        $this->service->trend($this->range());
        $this->service->byProduct($this->range());

        foreach ($this->db->queries as $q) {
            self::assertStringNotContainsString('WHERE Array', $q['sql']);
            self::assertStringNotContainsString('AND Array', $q['sql']);
            self::assertStringContainsString('WHERE ', $q['sql']);
            self::assertStringNotContainsString('WHERE  AND', $q['sql'], 'empty predicate list');
        }
    }

    public function testWindowBoundsArriveAsBoundParametersNotInlinedLiterals(): void
    {
        $range = $this->range('7d');
        $this->db->on('AS bucket', []);

        $this->service->trend($range);

        $query = $this->db->queries[0];

        // 2026-09-28 00:00:00 / 2026-10-04 12:00:00 must be bound, not pasted in.
        self::assertStringNotContainsString('2026-09-28', $query['sql']);
        self::assertContains('2026-09-28 00:00:00', $query['params']);
        self::assertContains('2026-10-04 12:00:00', $query['params']);
        self::assertSame($range->sqlParams(), array_slice($query['params'], 0, 2));
    }

    // ------------------------------------------------------------------ totals

    public function testTotalsCompareAgainstThePriorWindow(): void
    {
        $this->db->returnsOne(['total' => 250]);

        $totals = $this->service->totals($this->range('30d'));

        self::assertSame(250.0, $totals['current']);
        // The stub answers both windows with the same canned row.
        self::assertSame(250.0, $totals['prior']);
        self::assertSame(0.0, $totals['delta_pct']);
    }

    public function testDeltaIsNullWhenThereIsNoPriorRevenueToCompareAgainst(): void
    {
        $this->db->returnsOne(['total' => 0]);

        self::assertNull($this->service->totals($this->range('30d'))['delta_pct']);
    }

    public function testTotalsDefaultToZeroWhenTheDatabaseReturnsNothing(): void
    {
        $this->db->returnsOne(null);

        self::assertSame(0.0, $this->service->totals($this->range('30d'))['current']);
    }

    // ------------------------------------------------------- currency handling

    /**
     * `payments.currency` mixes BDT and USD rows. Summing the raw `amount`
     * column added BDT 144 to USD 45 and rendered the total with a '$' symbol,
     * and MRR is USD-canonical, so the two were never comparable.
     *
     * Every money aggregate must therefore normalise BDT rows to USD.
     */
    public function testEveryMoneyAggregateNormalisesBdtToUsd(): void
    {
        $rate = GatewayFactory::bdtRate();

        $this->db->on('FROM payments p', []);
        $this->service->trend($this->range('30d'), ['product' => 'app']);
        $trend = $this->db->sql();
        self::assertStringContainsString("IN ('BDT'", $trend, 'trend');
        self::assertStringContainsString((string) $rate, $trend, 'trend rate');
        self::assertStringContainsString('p.currency', $trend, 'trend qualifies currency');
        self::assertSame(
            0,
            preg_match('/SUM\((?!CASE)/', $trend),
            'trend must not sum the raw amount column'
        );

        $this->db->on('FROM payments', []);
        $this->service->byVariant($this->range('30d'));
        self::assertStringContainsString("IN ('BDT'", $this->db->sql());

        $this->db->on('FROM payments', []);
        $this->service->byGateway($this->range('30d'));
        self::assertStringContainsString("IN ('BDT'", $this->db->sql());

        // The product split joins `plans`, which also has a `currency` column —
        // an unqualified reference is ambiguous there.
        $this->db->reset();
        $this->db->on('FROM payments p', []);
        $this->service->byProduct($this->range('30d'));
        $byProduct = $this->db->sql();
        self::assertStringContainsString("IN ('BDT'", $byProduct);
        self::assertStringContainsString('p.currency', $byProduct);
        self::assertSame(
            0,
            preg_match('/COALESCE\(currency/', $byProduct),
            'currency must be qualified once plans is joined'
        );
    }

    public function testLifetimeTotalIsCurrencyNormalised(): void
    {
        $this->db->returnsOne(['total' => 46.31]);

        self::assertSame(46.31, $this->service->lifetimeTotal());
        self::assertStringContainsString("IN ('BDT'", $this->db->sql());
        self::assertStringNotContainsString('deleted_at', $this->db->sql());
    }

    // --------------------------------------------------------------------- MRR

    /**
     * B12: MRR/ARR ignored `plans.active`, so a retired plan kept inflating
     * recurring revenue for as long as a subscription pointed at it.
     */
    public function testMrrExcludesRetiredPlans(): void
    {
        $this->db->on('FROM subscriptions s', []);

        $this->service->mrr();

        $sql = $this->db->sql();
        self::assertStringContainsString("s.status = 'active'", $sql);
        self::assertStringContainsString('pl.active = 1', $sql);
    }

    /**
     * A filtered dashboard reconciled a filtered collection against a global
     * MRR, so every product/variant view showed the same gap as the unfiltered
     * one. MRR has to be scoped exactly the way collected revenue is.
     */
    public function testMrrHonoursProductAndVariantFilters(): void
    {
        $this->db->on('FROM subscriptions s', []);
        $this->service->mrr(['product' => 'node']);
        $sql = $this->db->sql();
        self::assertStringContainsString('pl.product = ?', $sql);
        self::assertContains('node', $this->db->params());

        $this->db->reset();
        $this->db->on('FROM subscriptions s', []);
        $this->service->mrr(['variant' => 'bd']);
        $sql = $this->db->sql();
        // The market a subscription was sold under is the license snapshot.
        self::assertStringContainsString('JOIN licenses lic ON lic.id = s.license_id', $sql);
        self::assertStringContainsString('lic.variant = ?', $sql);
        self::assertContains('bd', $this->db->params());

        $this->db->reset();
        $this->db->on('FROM subscriptions s', []);
        $this->service->mrr();
        $sql = $this->db->sql();
        self::assertStringNotContainsString('lic.variant', $sql);
        self::assertStringNotContainsString('pl.product = ?', $sql);
    }

    public function testReconciliationScopesMrrToTheSameFiltersAsCollected(): void
    {
        $this->db->on('FROM subscriptions s', []);
        $this->db->returnsOne(['total' => 12]);

        $this->service->reconcile($this->range('30d'), ['product' => 'node']);

        self::assertStringContainsString('pl.product = ?', $this->db->sql());
    }

    public function testMonthlyPlansContributeTheirFullPrice(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '49.00', 'period' => 'monthly'],
        ]);

        $mrr = $this->service->mrr();

        self::assertSame(49.0, $mrr['mrr']);
        self::assertSame(588.0, $mrr['arr']);
        self::assertSame(1, $mrr['active_subscriptions']);
    }

    public function testYearlyPlansContributeATwelfthOfTheirPrice(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '490.00', 'period' => 'yearly'],
        ]);

        self::assertSame(40.83, $this->service->mrr()['mrr']);
        self::assertSame(490.0, $this->service->mrr()['arr']);
    }

    public function testAnnualIsTreatedAsYearly(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '120.00', 'period' => 'annual'],
        ]);

        self::assertSame(10.0, $this->service->mrr()['mrr']);
    }

    public function testPeriodMatchingIsCaseInsensitive(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '120.00', 'period' => 'Yearly'],
        ]);

        self::assertSame(10.0, $this->service->mrr()['mrr']);
    }

    public function testMrrSumsAMixedBook(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '30.00', 'period' => 'monthly'],
            ['id' => 2, 'price' => '30.00', 'period' => 'monthly'],
            ['id' => 3, 'price' => '120.00', 'period' => 'yearly'],
        ]);

        $mrr = $this->service->mrr();

        self::assertSame(70.0, $mrr['mrr']);
        self::assertSame(3, $mrr['active_subscriptions']);
    }

    public function testMrrIsZeroWithNoActiveSubscriptions(): void
    {
        $this->db->on('FROM subscriptions s', []);

        $mrr = $this->service->mrr();

        self::assertSame(0.0, $mrr['mrr']);
        self::assertSame(0.0, $mrr['arr']);
        self::assertSame(0, $mrr['active_subscriptions']);
    }

    // -------------------------------------------------------------- reconcile

    /**
     * B4: MRR and collected revenue came from different tables and were printed
     * side by side with no relationship. The reconciliation must state which of
     * the four divergence cases applies.
     */
    public function testReconciliationFlagsRevenueWithNoSubscriptions(): void
    {
        $this->db->on('FROM subscriptions s', []);
        $this->db->returnsOne(['total' => 500]);

        $result = $this->service->reconcile($this->range('30d'));

        self::assertSame('revenue_without_mrr', $result['status']);
        self::assertNotSame('', $result['note']);
    }

    public function testReconciliationFlagsSubscriptionsWithNoCollections(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '49.00', 'period' => 'monthly'],
        ]);
        $this->db->returnsOne(['total' => 0]);

        $result = $this->service->reconcile($this->range('30d'));

        self::assertSame('mrr_without_revenue', $result['status']);
    }

    public function testReconciliationReportsEmptyWhenThereIsNothingAtAll(): void
    {
        $this->db->on('FROM subscriptions s', []);
        $this->db->returnsOne(['total' => 0]);

        self::assertSame('empty', $this->service->reconcile($this->range('30d'))['status']);
    }

    public function testReconciliationIsSatisfiedWhenCollectionsTrackMrr(): void
    {
        // $49 MRR over a ~30 day window expects roughly $49 collected.
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '49.00', 'period' => 'monthly'],
        ]);
        $this->db->returnsOne(['total' => 49]);

        $result = $this->service->reconcile($this->range('30d'));

        self::assertSame('reconciled', $result['status']);
        self::assertSame(0.0, abs($result['gap']));
    }

    public function testReconciliationCallsOutOneOffSalesAboveRunRate(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '49.00', 'period' => 'monthly'],
        ]);
        $this->db->returnsOne(['total' => 900]);

        $result = $this->service->reconcile($this->range('30d'));

        self::assertSame('divergent', $result['status']);
        self::assertGreaterThan(0, $result['gap']);
        self::assertStringContainsString('exceeds', $result['note']);
    }

    public function testReconciliationCallsOutShortfallAgainstRunRate(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '49.00', 'period' => 'monthly'],
        ]);
        $this->db->returnsOne(['total' => 2]);

        $result = $this->service->reconcile($this->range('30d'));

        self::assertSame('divergent', $result['status']);
        self::assertLessThan(0, $result['gap']);
        self::assertStringContainsString('short', $result['note']);
    }

    public function testReconciliationCarriesBothFiguresForDisplay(): void
    {
        $this->db->on('FROM subscriptions s', [
            ['id' => 1, 'price' => '49.00', 'period' => 'monthly'],
        ]);
        $this->db->returnsOne(['total' => 49]);

        $result = $this->service->reconcile($this->range('30d'));

        foreach (['mrr', 'arr', 'active_subscriptions', 'collected_window', 'collected_prior', 'gap', 'status', 'note'] as $key) {
            self::assertArrayHasKey($key, $result);
        }
    }

    // ----------------------------------------------------------------- gateway

    public function testGatewayMixIsSortedByRevenueDescending(): void
    {
        $this->db->on('GROUP BY gateway', [
            ['gateway' => 'manual', 'total' => 10],
            ['gateway' => 'paddle', 'total' => 300],
            ['gateway' => 'paypal', 'total' => 90],
        ]);

        $mix = $this->service->byGateway($this->range());

        self::assertSame(['paddle', 'paypal', 'manual'], array_keys($mix));
        self::assertSame(300.0, $mix['paddle']);
    }

    public function testGatewayNamesAreNormalisedAndBlanksBecomeManual(): void
    {
        $this->db->on('GROUP BY gateway', [
            ['gateway' => '  PADDLE ', 'total' => 50],
            ['gateway' => '', 'total' => 25],
        ]);

        $mix = $this->service->byGateway($this->range());

        self::assertSame(['paddle', 'manual'], array_keys($mix));
    }

    public function testRepeatedGatewayRowsAreSummedNotOverwritten(): void
    {
        $this->db->on('GROUP BY gateway', [
            ['gateway' => 'paddle', 'total' => 50],
            ['gateway' => 'paddle', 'total' => 25],
        ]);

        self::assertSame(75.0, $this->service->byGateway($this->range())['paddle']);
    }

    // ------------------------------------------------------------- by variant

    public function testVariantSplitAlwaysCoversBothVariantsInCanonicalOrder(): void
    {
        $this->db->on('GROUP BY variant', [['variant' => 'int', 'total' => 400]]);

        $split = $this->service->byVariant($this->range());

        self::assertSame(['bd', 'int'], array_keys($split));
        self::assertSame(0.0, $split['bd']);
        self::assertSame(400.0, $split['int']);
    }

    public function testUnknownVariantValuesAreNormalisedRatherThanCreatingNewBuckets(): void
    {
        $this->db->on('GROUP BY variant', [
            ['variant' => 'BD', 'total' => 100],
            ['variant' => null, 'total' => 7],
        ]);

        $split = $this->service->byVariant($this->range());

        self::assertSame(['bd', 'int'], array_keys($split));
        self::assertSame(100.0, $split['bd']);
        self::assertSame(7.0, $split['int'], 'null variant falls back to int');
    }

    // -------------------------------------------------------------- by product

    public function testProductRevenueSplitCoversAllFourProductsAndReportsUnattributed(): void
    {
        $this->db->on('LEFT JOIN plans pl', [
            ['product' => 'app', 'total' => 500],
            ['product' => 'node', 'total' => 200],
            ['product' => null, 'total' => 45],
        ]);

        $split = $this->service->byProduct($this->range());

        self::assertSame(['app', 'php', 'theme', 'node'], array_keys($split['by_product']));
        self::assertSame(500.0, $split['by_product']['app']);
        self::assertSame(200.0, $split['by_product']['node']);
        self::assertSame(0.0, $split['by_product']['php']);
        self::assertSame(45.0, $split['unattributed'], 'payments with a deleted plan must not vanish');
    }

    public function testProductRevenueUsesALeftJoinSoOrphanPaymentsAreStillCounted(): void
    {
        $this->db->on('LEFT JOIN plans pl', []);

        $this->service->byProduct($this->range());

        self::assertStringContainsString('LEFT JOIN plans pl ON pl.id = p.plan_id', $this->db->sql());
    }
}