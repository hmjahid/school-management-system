<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\DateRange;
use App\Services\Analytics\TrafficService;
use Tests\Support\AggregateDatabaseStub;
use Tests\TestCase;

/**
 * TrafficService builds each funnel stage's WHERE clause with
 * `DateRange::where()`, which takes its params array **by reference**. Passing an
 * inline `$params = []` into that argument list is a fatal error in PHP
 * ("could not be passed by reference"), so these tests execute the real code path
 * rather than only asserting on the emitted SQL.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §6.3 (W14, W19)
 */
class TrafficServiceTest extends TestCase
{
    private AggregateDatabaseStub $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new AggregateDatabaseStub();
    }

    private function range(): DateRange
    {
        return DateRange::fromRequest(['range' => '30d'], new \DateTimeImmutable('2026-10-04 12:00:00', new \DateTimeZone('UTC')));
    }

    /**
     * Rules are matched in insertion order, so the most specific needle goes
     * first and the generic visitor count last.
     */
    private function scriptFunnel(int $visitors, int $views, int $checkout, int $paid): void
    {
        $this->db->on("v.path LIKE '/products/%'", [['c' => $views]]);
        $this->db->on("v.path LIKE '/checkout%'", [['c' => $checkout]]);
        $this->db->on('FROM payments', [['c' => $paid]]);
        $this->db->on('FROM visitors v', [['c' => $visitors]]);
    }

    public function testFunnelReturnsTheFourStagesInOrder(): void
    {
        $this->scriptFunnel(1840, 620, 94, 13);

        $funnel = (new TrafficService($this->db))->funnel($this->range());

        self::assertSame(
            [
                ['key' => 'visitors', 'label' => 'Visitors', 'value' => 1840],
                ['key' => 'engaged', 'label' => 'Product views', 'value' => 620],
                ['key' => 'checkout', 'label' => 'Checkout', 'value' => 94],
                ['key' => 'paid', 'label' => 'Paid', 'value' => 13],
            ],
            $funnel
        );
    }

    public function testFunnelExcludesBots(): void
    {
        $this->scriptFunnel(10, 4, 2, 1);

        (new TrafficService($this->db))->funnel($this->range());

        // Every visitor stage must filter bots out.
        self::assertGreaterThanOrEqual(3, substr_count($this->db->sql(), 'v.is_bot = 0'));
    }

    public function testFunnelBindsTheWindowParameters(): void
    {
        $this->scriptFunnel(1, 1, 1, 1);

        (new TrafficService($this->db))->funnel($this->range());

        foreach ($this->db->queries as $query) {
            self::assertCount(2, $query['params'], 'a funnel stage did not bind the date window');
        }
    }

    public function testFunnelDegradesToZeroWithNoTraffic(): void
    {
        $funnel = (new TrafficService($this->db))->funnel($this->range());

        foreach ($funnel as $stage) {
            self::assertSame(0, $stage['value']);
        }
    }

    public function testCountriesRollTheTailIntoAnOtherBucket(): void
    {
        $this->db->on('GROUP BY country', [
            ['country' => 'Bangladesh', 'c' => 50],
            ['country' => 'United States', 'c' => 30],
            ['country' => 'India', 'c' => 20],
            ['country' => 'United Kingdom', 'c' => 10],
            ['country' => 'Canada', 'c' => 5],
            ['country' => 'Australia', 'c' => 4],
            ['country' => 'Germany', 'c' => 3],
            ['country' => 'France', 'c' => 2],
        ]);

        $countries = (new TrafficService($this->db))->countries($this->range(), 6);

        self::assertCount(7, $countries);
        self::assertSame(50, $countries['Bangladesh']);
        self::assertSame(4, $countries['Australia']);
        self::assertSame(5, $countries['Other']);
        self::assertArrayNotHasKey('Germany', $countries);
    }

    public function testCountriesIsEmptyWhenThereAreNoVisitors(): void
    {
        self::assertSame([], (new TrafficService($this->db))->countries($this->range()));
    }
}
