<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\DateRange;
use Tests\TestCase;

class DateRangeTest extends TestCase
{
    /** Pinned "now" so every case is deterministic. */
    private const NOW = '2026-10-04 12:00:00';

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC'));
    }

    private function range(string $query = '30d'): DateRange
    {
        return DateRange::fromRequest(['range' => $query], $this->now());
    }

    // ---------------------------------------------------------------- presets

    public function testTodayCoversMidnightToNow(): void
    {
        $range = $this->range('today');

        self::assertSame('2026-10-04 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertSame('2026-10-04 12:00:00', $range->to->format('Y-m-d H:i:s'));
        self::assertSame(DateRange::BUCKET_HOUR, $range->bucket);
    }

    /**
     * B5: the pre-existing series was anchored to `DATE_FORMAT(NOW(),'%Y-%m-01')`,
     * so any window starting mid-month silently dropped the partial month on the
     * left edge. Presets must be day-aligned rolling windows, never month-anchored.
     */
    public function testSevenDayPresetIsSevenCalendarDaysNotMonthAnchored(): void
    {
        $range = $this->range('7d');

        self::assertSame('2026-09-28 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertNotSame('2026-10-01 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertCount(7, $range->buckets());
    }

    public function testThirtyDayPresetSpansThirtyCalendarDays(): void
    {
        $range = $this->range('30d');

        self::assertSame('2026-09-05 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertCount(30, $range->buckets());
        self::assertSame(DateRange::BUCKET_DAY, $range->bucket);
    }

    public function testNinetyDayPresetBucketsByWeek(): void
    {
        $range = $this->range('90d');

        self::assertSame('2026-07-07 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertSame(DateRange::BUCKET_WEEK, $range->bucket);
        // Weekly points stay readable; roughly 13 of them.
        self::assertLessThanOrEqual(15, count($range->buckets()));
        self::assertGreaterThanOrEqual(12, count($range->buckets()));
    }

    public function testTwelveMonthPresetTouchesThirteenCalendarMonths(): void
    {
        $range = $this->range('12mo');

        $keys = array_map(static fn ($d) => $d->format('Y-m'), $range->buckets());

        // Rolling back exactly one year from 4 Oct lands in Oct of the prior year,
        // so the window touches 13 calendar months — the current one is partial.
        self::assertSame('2025-10', $keys[0]);
        self::assertSame('2026-10', $keys[count($keys) - 1]);
        self::assertCount(13, $keys);
    }

    public function testYearToDateStartsOnJanuaryFirst(): void
    {
        $range = $this->range('ytd');

        self::assertSame('2026-01-01 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertSame('2026-10-04 12:00:00', $range->to->format('Y-m-d H:i:s'));
    }

    public function testUnknownPresetFallsBackToThirtyDaysRatherThanErroring(): void
    {
        $range = $this->range('wibble');

        self::assertSame('30d', $range->preset);
        self::assertCount(30, $range->buckets());
    }

    public function testAllTimeIsFlaggedAndFloorDated(): void
    {
        $range = $this->range('all');

        self::assertTrue($range->isAllTime());
        self::assertSame('2000-01-01 00:00:00', $range->from->format('Y-m-d H:i:s'));
    }

    public function testExplicitBoundsOverrideThePreset(): void
    {
        $range = DateRange::fromRequest([
            'range' => '30d',
            'from'  => '2026-01-15',
            'to'    => '2026-02-15',
        ], $this->now());

        self::assertSame('2026-01-15 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertSame('2026-02-15 23:59:59', $range->to->format('Y-m-d H:i:s'));
        self::assertSame('custom', $range->preset);
    }

    public function testInvertedExplicitBoundsAreSwappedRatherThanRenderingAnEmptyChart(): void
    {
        $range = DateRange::fromRequest([
            'from' => '2026-05-10',
            'to'   => '2026-05-01',
        ], $this->now());

        self::assertTrue($range->from <= $range->to);
    }

    public function testMalformedExplicitBoundsFallBackToThePreset(): void
    {
        $range = DateRange::fromRequest([
            'range' => '7d',
            'from'  => 'not-a-date',
            'to'    => 'also-not-a-date',
        ], $this->now());

        self::assertSame('7d', $range->preset);
        self::assertSame('2026-09-28 00:00:00', $range->from->format('Y-m-d H:i:s'));
    }

    // ------------------------------------------------------------ prior range

    public function testPriorWindowSitsImmediatelyBeforeAndIsComparableInLength(): void
    {
        $range  = $this->range('30d');
        $prior  = $range->prior();

        self::assertTrue($prior->to < $range->from);
        self::assertLessThanOrEqual(
            1.5,
            abs($range->spanInDays() - $prior->spanInDays()),
            'prior window must be the same length for a meaningful delta'
        );
    }

    public function testPriorWindowsDoNotOverlapTheCurrentOne(): void
    {
        $range = $this->range('30d');
        $prior = $range->prior();

        foreach ($prior->buckets() as $bucket) {
            self::assertFalse($range->contains($bucket), 'bucket leaked into the current window');
        }
    }

    // ---------------------------------------------------------------- buckets

    public function testBucketsAreDenseAscendingAndUnique(): void
    {
        foreach (['today', '7d', '30d', '90d', '12mo', 'ytd'] as $preset) {
            $buckets = $this->range($preset)->buckets();
            self::assertNotEmpty($buckets, "{$preset} produced no buckets");

            $times = array_map(static fn ($d) => $d->getTimestamp(), $buckets);
            $sorted = $times;
            sort($sorted);

            self::assertSame($sorted, $times, "{$preset} buckets are not ascending");
            self::assertSame($sorted, array_values(array_unique($sorted)), "{$preset} has duplicate buckets");
        }
    }

    public function testBucketsStopAtTheEndOfTheWindow(): void
    {
        foreach (['today', '7d', '30d', '90d', '12mo', 'ytd'] as $preset) {
            $range   = $this->range($preset);
            $buckets = $range->buckets();
            $last    = $buckets[count($buckets) - 1];

            self::assertTrue($last <= $range->to, "{$preset} last bucket runs past the window");
        }
    }

    public function testTheFirstBucketNeverPrecedesTheWindowByMoreThanOnePeriod(): void
    {
        // Aligned buckets (weeks start Monday, hours start on the hour) legitimately
        // begin a little before `from`. That is what makes SQL `GROUP BY` agree with
        // the PHP zero-fill — but it must be a partial bucket, never a whole extra one.
        foreach (['90d'] as $preset) {
            $range = $this->range($preset);
            $first = $range->buckets()[0];

            self::assertLessThanOrEqual(
                7 * 86400,
                $first->getTimestamp() - $range->from->getTimestamp(),
                "{$preset} first bucket starts more than a period early"
            );
        }
    }

    public function testAllTimeDoesNotProduceAnUnboundedBucketList(): void
    {
        $buckets = $this->range('all')->buckets();

        // Guarded at 4096 rather than looping from 2000 to now forever.
        self::assertLessThanOrEqual(4096, count($buckets));
    }

    public function testBucketKeyMatchesTheBucketARealTimestampFallsInto(): void
    {
        $range = $this->range('12mo');

        $boundary = $range->buckets()[3];
        $key      = $range->bucketKey($boundary);

        self::assertSame($boundary->format($range->keyFormat()), $key);
    }

    public function testBucketKeyAcceptsAStringTimestamp(): void
    {
        $range = $this->range('30d');

        self::assertSame('2026-10-04', $range->bucketKey('2026-10-04 09:30:00'));
    }

    public function testBucketLabelsAreShortAndNonEmpty(): void
    {
        foreach (['today', '7d', '90d', '12mo'] as $preset) {
            $range = $this->range($preset);

            foreach ($range->buckets() as $bucket) {
                $label = $range->bucketLabel($bucket);
                self::assertNotSame('', $label);
                self::assertLessThanOrEqual(8, strlen($label), "{$preset} label '{$label}' is too long for an axis");
            }
        }
    }

    public function testContainsAcceptsBothStringsAndDateTimes(): void
    {
        $range = $this->range('30d');

        self::assertTrue($range->contains('2026-10-01 00:00:00'));
        self::assertTrue($range->contains(new \DateTimeImmutable('2026-10-01', new \DateTimeZone('UTC'))));
        self::assertFalse($range->contains('2026-01-01 00:00:00'));
        self::assertFalse($range->contains('2027-01-01 00:00:00'));
    }

    // ------------------------------------------------------------ bucket sizing

    public function testBucketSizeThresholdsProduceReadablePointCounts(): void
    {
        self::assertSame(DateRange::BUCKET_HOUR, DateRange::bucketForDays(1));
        self::assertSame(DateRange::BUCKET_HOUR, DateRange::bucketForDays(2));
        self::assertSame(DateRange::BUCKET_DAY, DateRange::bucketForDays(3));
        self::assertSame(DateRange::BUCKET_DAY, DateRange::bucketForDays(45));
        self::assertSame(DateRange::BUCKET_DAY, DateRange::bucketForDays(120));
        self::assertSame(DateRange::BUCKET_WEEK, DateRange::bucketForDays(121));
        self::assertSame(DateRange::BUCKET_WEEK, DateRange::bucketForDays(400));
        self::assertSame(DateRange::BUCKET_MONTH, DateRange::bucketForDays(401));
    }

    // ------------------------------------------------------------------ SQL

    public function testWhereBindsBoundsInOrderWithoutEmbeddingLiterals(): void
    {
        $range  = $this->range('7d');
        $params = [];

        $sql = $range->where('paid_at', $params);

        self::assertSame('paid_at >= ? AND paid_at <= ?', $sql);
        self::assertSame(['2026-09-28 00:00:00', '2026-10-04 12:00:00'], $params);
        self::assertStringNotContainsString('2026-09-28', $sql);
    }

    public function testSqlParamsMirrorWhereExactly(): void
    {
        $range  = $this->range('30d');
        $params = [];
        $range->where('paid_at', $params);

        self::assertSame($range->sqlParams(), $params);
    }

    public function testAllTimeStillBindsAnIndexFriendlyFloor(): void
    {
        $params = [];
        $sql    = $this->range('all')->where('paid_at', $params);

        // A `>= '2000-01-01'` bound keeps the planner on the created_at index;
        // omitting the predicate entirely would force a full scan.
        self::assertSame('paid_at >= ? AND paid_at <= ?', $sql);
        self::assertSame('2000-01-01 00:00:00', $params[0]);
    }

    /**
     * The SQL `GROUP BY` key and the PHP zero-fill key must agree, or every
     * bucket silently renders as zero. PHP and MySQL spell these formats
     * differently (`o`/`%x`, `W`/`%v`, `%H`), so pin the mapping with a test
     * rather than trusting two hand-written strings to stay in sync.
     */
    public function testSqlBucketKeyFormatMatchesThePhpKeyFormat(): void
    {
        $equivalents = [
            DateRange::BUCKET_HOUR  => "'%Y-%m-%d %H'",
            DateRange::BUCKET_DAY   => null,   // special-cased below: DATE() not DATE_FORMAT()
            DateRange::BUCKET_WEEK  => "'%x-W%v'",
            DateRange::BUCKET_MONTH => "'%Y-%m'",
        ];

        $phpFormats = [
            DateRange::BUCKET_HOUR  => 'Y-m-d H',
            DateRange::BUCKET_DAY   => 'Y-m-d',
            DateRange::BUCKET_WEEK  => 'o-\WW',
            DateRange::BUCKET_MONTH => 'Y-m',
        ];

        foreach ($equivalents as $bucket => $sqlFormat) {
            $range = DateRange::between(
                new \DateTimeImmutable('2026-01-01', new \DateTimeZone('UTC')),
                new \DateTimeImmutable('2026-12-31', new \DateTimeZone('UTC')),
                $bucket,
            );

            self::assertSame($phpFormats[$bucket], $range->keyFormat(), "{$bucket} php format drifted");

            $expr = $range->sqlBucketExpression('paid_at');

            if ($sqlFormat === null) {
                // DATE() yields Y-m-d directly.
                self::assertSame('DATE(paid_at)', $expr, "{$bucket} sql form drifted");
                continue;
            }

            self::assertStringContainsString(
                $sqlFormat,
                $expr,
                "{$bucket} sql format drifted from its php counterpart"
            );
        }
    }

    public function testBucketExpressionQualifiesTheGivenColumn(): void
    {
        $range = $this->range('12mo');

        self::assertStringContainsString('p.paid_at', $range->sqlBucketExpression('p.paid_at'));
    }

    public function testEveryBucketResolvesToASqlExpression(): void
    {
        foreach (['today', '7d', '90d', '12mo'] as $preset) {
            $expr = $this->range($preset)->sqlBucketExpression('paid_at');

            self::assertNotSame('', $expr);
            self::assertStringContainsString('paid_at', $expr);
        }
    }

    // ----------------------------------------------------------------- misc

    public function testCacheKeyIsStablePerWindowAndVariesWithBucket(): void
    {
        self::assertSame($this->range('30d')->cacheKey(), $this->range('30d')->cacheKey());
        self::assertNotSame($this->range('30d')->cacheKey(), $this->range('7d')->cacheKey());

        $custom = DateRange::between(
            new \DateTimeImmutable('2026-01-01', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-12-31', new \DateTimeZone('UTC')),
            DateRange::BUCKET_MONTH,
        );
        self::assertNotSame($custom->cacheKey(), $this->range('12mo')->cacheKey());
    }

    public function testEveryPresetHasAReadableLabel(): void
    {
        foreach (DateRange::PRESETS as $preset) {
            $label = $this->range($preset)->label();
            self::assertNotSame('', $label, "preset '{$preset}' produced an empty label");
        }
    }

    public function testCustomWindowLabelShowsBothEnds(): void
    {
        $range = DateRange::fromRequest(['from' => '2026-01-15', 'to' => '2026-02-15'], $this->now());

        self::assertStringContainsString('Jan 2026', $range->label());
        self::assertStringContainsString('Feb 2026', $range->label());
    }
}