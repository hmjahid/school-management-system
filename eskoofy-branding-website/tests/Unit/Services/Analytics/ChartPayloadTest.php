<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\ChartPayload;
use App\Services\Catalog;
use Tests\TestCase;

/**
 * The chart configs are built in PHP and handed to the browser as JSON, so a
 * mistake here surfaces as a silently blank chart. These cover the shapes
 * `public/js/charts.js` depends on.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §5.3
 */
class ChartPayloadTest extends TestCase
{
    public function testAreaEmitsEveryLabelAndACumulativeSeries(): void
    {
        $spec = ChartPayload::area([
            ['label' => 'Jan 25', 'value' => 10],
            ['label' => 'Feb 25', 'value' => 5],
            ['label' => 'Mar 25', 'value' => 20],
        ], ['cumulative' => true]);

        self::assertSame('line', $spec['type']);
        self::assertSame(['Jan 25', 'Feb 25', 'Mar 25'], $spec['labels']);
        self::assertCount(2, $spec['datasets']);
        self::assertSame([10.0, 5.0, 20.0], $spec['datasets'][0]['data']);
        self::assertSame([10.0, 15.0, 35.0], $spec['datasets'][1]['data']);
        self::assertSame('y1', $spec['datasets'][1]['yAxisID']);
    }

    public function testSparklineIsAxisFreeAndSingleSeries(): void
    {
        $spec = ChartPayload::spark([1, 2, 3], 'var(--brand)');

        self::assertSame(['var(--brand)'], $spec['colors']);
        self::assertTrue($spec['options']['chart']['sparkline']['enabled']);
        self::assertFalse($spec['options']['dataLabels']['enabled']);
        self::assertSame([[1.0, 2.0, 3.0]], array_column($spec['options']['series'], 'data'));
    }

    public function testStackedBarsShareTheCategoryAxis(): void
    {
        $spec = ChartPayload::stacked(['A', 'B'], [
            ['label' => 'New', 'data' => [1, 2], 'color' => 'var(--brand)'],
            ['label' => 'Renewal', 'data' => [3, 4], 'color' => 'var(--success)'],
        ]);

        self::assertTrue($spec['scales']['x']['stacked']);
        self::assertTrue($spec['scales']['y']['stacked']);
        self::assertSame(['var(--brand)', 'var(--success)'], array_column($spec['datasets'], 'color'));
    }

    public function testDonutKeepsColoursAlignedWithLabels(): void
    {
        $spec = ChartPayload::donut(['BD', 'INT'], [3, 1], ['var(--v-bd)', 'var(--v-int)']);

        self::assertSame(['BD', 'INT'], $spec['options']['labels']);
        self::assertSame([3.0, 1.0], $spec['options']['series']);
        self::assertSame(['var(--v-bd)', 'var(--v-int)'], $spec['colors']);
        self::assertSame('donut', $spec['options']['chart']['type']);
    }

    public function testFunnelSeriesIsAnXYList(): void
    {
        $spec = ChartPayload::funnel(['Visitors', 'Paid'], [100, 4]);

        self::assertSame(
            [['x' => 'Visitors', 'y' => 100.0], ['x' => 'Paid', 'y' => 4.0]],
            $spec['options']['series'][0]['data']
        );
    }

    public function testHeatmapEmitsASeriesPerProduct(): void
    {
        $spec = ChartPayload::heatmap(
            [['name' => 'School App', 'data' => [1, 2]], ['name' => 'Node.js App', 'data' => [0, 3]]],
            ['Oct 25', 'Nov 25']
        );

        self::assertCount(2, $spec['options']['series']);
        self::assertSame(['Oct 25', 'Nov 25'], $spec['options']['xaxis']['categories']);
        self::assertSame('heatmap', $spec['options']['chart']['type']);
    }

    public function testProductColoursFollowCatalogOrder(): void
    {
        self::assertSame(array_values(Catalog::colors()), ChartPayload::productColors());
    }

    public function testJsonIsSafeInsideADoubleQuotedAttribute(): void
    {
        $encoded = ChartPayload::json(ChartPayload::area([
            ['label' => 'He said "hi" & left', 'value' => 1],
        ]));

        self::assertStringNotContainsString('"', $encoded, 'a raw quote would break the attribute');
        self::assertStringContainsString('&quot;', $encoded);

        $decoded = json_decode(html_entity_decode($encoded, ENT_QUOTES, 'UTF-8'), true);
        self::assertIsArray($decoded);
        self::assertSame('He said "hi" & left', $decoded['labels'][0]);
    }
}
