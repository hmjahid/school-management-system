<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\DateRange;
use App\Services\Analytics\KpiService;
use Tests\Support\AggregateDatabaseStub;
use Tests\TestCase;

/**
 * KpiService builds every series with `DateRange::where()`, which takes its
 * params array by reference — the same shape that fatals if fed an inline
 * assignment. These execute the real methods and assert the dense zero-fill.
 */
class KpiServiceTest extends TestCase
{
    private AggregateDatabaseStub $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new AggregateDatabaseStub();
    }

    private function range(): DateRange
    {
        return DateRange::fromRequest(
            ['range' => '7d'],
            new \DateTimeImmutable('2026-10-04 12:00:00', new \DateTimeZone('UTC'))
        );
    }

    public function testLicenseSeriesIsDenseAndZeroFilled(): void
    {
        $this->db->on('FROM licenses l', [['bucket' => '2026-10-01', 'total' => 3]]);

        $series = (new KpiService($this->db))->licensesSeries($this->range());

        self::assertCount(7, $series, 'a 7-day window must yield 7 buckets');

        $byKey = array_column($series, 'value', 'key');
        self::assertSame(3.0, $byKey['2026-10-01']);
        self::assertSame(0.0, $byKey['2026-10-02']);
    }

    public function testCustomerSeriesIsDense(): void
    {
        $this->db->on('FROM customers c', [['bucket' => '2026-09-28', 'total' => 2]]);

        $series = (new KpiService($this->db))->customersSeries($this->range());

        self::assertCount(7, $series);
        self::assertSame(2.0, array_column($series, 'value', 'key')['2026-09-28']);
    }

    public function testActivationSeriesJoinsLicensesForTheFilters(): void
    {
        $this->db->on('FROM license_activations a', [['bucket' => '2026-10-03', 'total' => 5]]);

        $series = (new KpiService($this->db))->activationsSeries($this->range(), ['product' => 'app']);

        self::assertCount(7, $series);
        self::assertStringContainsString('license_activations a LEFT JOIN licenses l', $this->db->sql());
        self::assertStringContainsString('l.product = ?', $this->db->sql());
    }

    public function testDeactivationSeriesIsDense(): void
    {
        $series = (new KpiService($this->db))->deactivationsSeries($this->range());

        self::assertCount(7, $series);
        self::assertSame([0.0], array_values(array_unique(array_column($series, 'value'))));
    }

    public function testRevenueSplitSeparatesNewFromRenewal(): void
    {
        $this->db->on('SUM(CASE WHEN', [[
            'bucket' => '2026-10-01',
            'fresh' => 4,
            'renewals' => 2,
        ]]);

        $split = (new KpiService($this->db))->revenueSplit($this->range());

        self::assertCount(7, $split['new']);
        self::assertCount(7, $split['renewal']);
        self::assertSame(4.0, array_column($split['new'], 'value', 'key')['2026-10-01']);
        self::assertSame(2.0, array_column($split['renewal'], 'value', 'key')['2026-10-01']);
        self::assertStringContainsString('EXISTS', $this->db->sql());
    }
}
