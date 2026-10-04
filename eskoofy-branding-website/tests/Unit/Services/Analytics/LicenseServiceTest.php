<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\LicenseService;
use Tests\Support\AggregateDatabaseStub;
use Tests\TestCase;

/**
 * Covers the derived-status read model and the expiring-soon consistency fix.
 *
 * Bug references come from docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.3.
 */
class LicenseServiceTest extends TestCase
{
    private AggregateDatabaseStub $db;

    private LicenseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new AggregateDatabaseStub();
        $this->service = new LicenseService($this->db);
    }

    // ------------------------------------------------------- derived status

    /**
     * B2: `expired` is not a stored status. It must be derived from `expires_at`,
     * otherwise an expired license keeps reporting as active.
     */
    public function testDerivedStatusGivesSuspendedAndCancelledPrecedenceOverExpiry(): void
    {
        $sql = LicenseService::derivedStatusSql();

        self::assertStringContainsString("status IN ('suspended', 'cancelled')", $sql);
        self::assertStringContainsString("expires_at <= NOW()", $sql);
        self::assertStringContainsString("'expired'", $sql);
        // Order matters: the administrative status must be tested first.
        self::assertLessThan(
            strpos($sql, 'expires_at <= NOW()'),
            strpos($sql, "status IN ('suspended', 'cancelled')")
        );
    }

    public function testDerivedStatusTreatsNullExpiryAsActive(): void
    {
        $sql = LicenseService::derivedStatusSql();

        // A perpetual license has no expires_at, so it must fall through to 'active'.
        self::assertStringContainsString('expires_at IS NOT NULL', $sql);
        self::assertStringContainsString("ELSE 'active'", $sql);
    }

    public function testDerivedStatusHonoursTheTableAlias(): void
    {
        self::assertStringContainsString('x.expires_at', LicenseService::derivedStatusSql('x'));
    }

    /**
     * The active filter is the single definition of "usable right now"; the KPI
     * and the list below it must both route through it.
     */
    public function testActiveFilterExcludesSoftDeletedRows(): void
    {
        $sql = LicenseService::activeSql();

        self::assertStringContainsString('deleted_at IS NULL', $sql);
        self::assertStringContainsString("status = 'active'", $sql);
        self::assertStringContainsString('expires_at > NOW()', $sql);
    }

    public function testEveryStatusHasCanonicalMetadata(): void
    {
        self::assertSame(
            ['active', 'suspended', 'expired', 'cancelled'],
            array_keys(LicenseService::STATUSES),
            'status order is the dashboard legend order'
        );

        foreach (LicenseService::STATUSES as $key => $meta) {
            self::assertSame($key, LicenseService::statusMeta($key)['key']);
            self::assertNotSame('', $meta['label']);
            self::assertContains($meta['tone'], ['success', 'warning', 'muted', 'danger']);
        }
    }

    public function testUnknownStatusDegradesToAMutedLabelRatherThanBreakingTheLegend(): void
    {
        $meta = LicenseService::statusMeta('refunded');

        self::assertSame('refunded', $meta['key']);
        self::assertSame('Refunded', $meta['label']);
        self::assertSame('muted', $meta['tone']);
    }

    public function testNullStatusDegradesGracefully(): void
    {
        $meta = LicenseService::statusMeta(null);

        self::assertSame('unknown', $meta['key']);
        self::assertSame('Unknown', $meta['label']);
    }

    public function testStatusLookupIsCaseAndWhitespaceTolerant(): void
    {
        self::assertSame('active', LicenseService::statusMeta('  ACTIVE ')['key']);
    }

    // --------------------------------------------------------- countsByStatus

    public function testCountsByStatusUsesTheDerivedExpressionNotTheStoredColumn(): void
    {
        $this->db->on('FROM licenses', [
            ['derived_status' => 'active', 'c' => 120],
            ['derived_status' => 'expired', 'c' => 18],
            ['derived_status' => 'suspended', 'c' => 2],
            ['derived_status' => 'cancelled', 'c' => 1],
        ]);

        $counts = $this->service->countsByStatus();

        // Rows came back active/expired/suspended/cancelled; the service reorders
        // them into canonical legend order regardless of what the DB returned.
        self::assertSame(
            ['active' => 120, 'suspended' => 2, 'expired' => 18, 'cancelled' => 1],
            $counts
        );
        self::assertSame(array_keys(LicenseService::STATUSES), array_keys($counts));

        $sql = $this->db->sql();
        self::assertStringContainsString(LicenseService::derivedStatusSql(), $sql);
        self::assertStringNotContainsString('GROUP BY l.status', $sql);
        // Regression: `GROUP BY status` binds to the physical `licenses.status`
        // column in MySQL/MariaDB and discards the derived expression, so every
        // expired license reported as 'active' and 'expired' was never emitted.
        self::assertStringContainsString('GROUP BY derived_status', $sql);
    }

    /**
     * The KPI tile and the list used to disagree because they applied different
     * predicates. Both now come from the same service, so the totals must be
     * derived from one and the same query shape.
     */
    public function testCountsByStatusAlwaysReturnsEveryStatusKeyEvenWhenZero(): void
    {
        $this->db->on('FROM licenses', []);

        $counts = $this->service->countsByStatus();

        self::assertSame(array_keys(LicenseService::STATUSES), array_keys($counts));
        self::assertSame([0, 0, 0, 0], array_values($counts));
    }

    public function testCountsByStatusCoercesStringCountsToIntegers(): void
    {
        $this->db->on('FROM licenses', [['derived_status' => 'active', 'c' => '7']]);

        $counts = $this->service->countsByStatus();

        self::assertSame(7, $counts['active']);
    }

    public function testCountsByStatusAppliesProductAndVariantFilters(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->countsByStatus(['product' => 'node', 'variant' => 'bd']);

        $sql    = $this->db->sql();
        $params = $this->db->queries[0]['params'];

        self::assertStringContainsString('l.product = ?', $sql);
        self::assertStringContainsString('l.variant = ?', $sql);
        self::assertContains('node', $params);
        self::assertContains('bd', $params);
    }

    public function testEmptyFiltersDoNotAddWhereClauses(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->countsByStatus(['product' => null, 'variant' => null]);

        self::assertStringNotContainsString('l.product = ?', $this->db->sql());
        self::assertStringNotContainsString('l.variant = ?', $this->db->sql());
    }

    public function testCountsByStatusExcludesSoftDeletedLicenses(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->countsByStatus();

        self::assertStringContainsString('l.deleted_at IS NULL', $this->db->sql());
    }

    // ------------------------------------------------------- countsByProduct

    /**
     * B3: the old local colour map knew app/theme/php but not node, so the Node.js
     * bar rendered in the app's blue. Counts now come keyed by catalog product.
     */
    public function testCountsByProductReturnsAllFourProducts(): void
    {
        $this->db->on('FROM licenses', [
            ['product' => 'app', 'c' => 90],
            ['product' => 'node', 'c' => 12],
        ]);

        $counts = $this->service->countsByProduct();

        self::assertSame(['app', 'php', 'theme', 'node'], array_keys($counts));
        self::assertSame(90, $counts['app']);
        self::assertSame(12, $counts['node']);
        self::assertSame(0, $counts['php'], 'products with no licenses must still be listed');
    }

    public function testCountsByProductSurfacesUnexpectedProductsUnderTheirOwnKey(): void
    {
        $this->db->on('FROM licenses', [
            ['product' => 'app', 'c' => 5],
            ['product' => 'legacy-thing', 'c' => 3],
        ]);

        $counts = $this->service->countsByProduct();

        self::assertSame(5, $counts['app']);
        // Retired/renamed products must be visible, not silently absorbed into a
        // neighbouring bar.
        self::assertSame(3, $counts['legacy-thing']);
        self::assertSame(4, count($counts) - 1, 'the four catalog products remain present');
    }

    public function testCountsByProductExcludesSoftDeletedRows(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->countsByProduct();

        self::assertStringContainsString('l.deleted_at IS NULL', $this->db->sql());
    }

    // ---------------------------------------------------------------- expiring

    /**
     * B1: the `expiring_soon` tile was counted across all licenses while the list
     * underneath was limited to active ones, so the number and the rows
     * disagreed. Both now share {@see activeSql}.
     */
    public function testExpiringListIsRestrictedToActuallyActiveLicenses(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->expiring(30, 10);

        $sql = $this->db->sql();
        self::assertStringContainsString('deleted_at IS NULL', $sql);
        self::assertStringContainsString("l.status = 'active'", $sql);
        self::assertStringContainsString('l.expires_at > NOW()', $sql);
        // BETWEEN implies "not already past", which is what makes the tile and
        // the list agree.
        self::assertStringContainsString('l.expires_at BETWEEN NOW()', $sql);
    }

    public function testExpiringWindowTracksTheRequestedDayCount(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->expiring(45, 10);

        // `$days` is a typed int clamped with max(1, ...), so interpolating it is
        // safe; MySQL needs it inline for INTERVAL n DAY.
        self::assertStringContainsString('INTERVAL 45 DAY', $this->db->sql());
    }

    public function testANonPositiveDayCountIsClampedToOne(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->expiring(0, 10);

        self::assertStringContainsString('INTERVAL 1 DAY', $this->db->sql());
    }

    public function testExpiringCountAndListShareTheSamePredicate(): void
    {
        $this->db->on('FROM licenses', []);
        $this->service->expiringCount(30);
        $countSql = $this->db->sql();

        $this->db->reset();
        $this->db->on('FROM licenses', []);
        $this->service->expiring(30, 10);
        $listSql = $this->db->sql();

        // Both must constrain to active, unexpired, unexpired-yet licences.
        foreach (['l.deleted_at IS NULL', "l.status = 'active'", 'l.expires_at > NOW()'] as $fragment) {
            self::assertStringContainsString($fragment, $countSql);
            self::assertStringContainsString($fragment, $listSql);
        }
    }

    public function testExpiringCountReturnsZeroWhenThereAreNoRows(): void
    {
        $this->db->on('FROM licenses', []);

        self::assertSame(0, $this->service->expiringCount(30));
    }

    public function testExpiringCoercesACountRowToAnInteger(): void
    {
        $this->db->on('FROM licenses', [['c' => '9']]);

        self::assertSame(9, $this->service->expiringCount(30));
    }

    public function testExpiringLimitIsClampedSoACallerCannotPullEveryRow(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->expiring(30, 5000);

        self::assertStringContainsString('LIMIT ' . LicenseService::EXPIRING_MAX_LIMIT, $this->db->sql());
        self::assertStringNotContainsString('LIMIT 5000', $this->db->sql());
    }

    public function testExpiringLimitOfZeroIsClampedToOne(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->expiring(30, 0);

        self::assertStringContainsString('LIMIT 1', $this->db->sql());
    }

    // ----------------------------------------------------------------- matrix

    public function testMatrixCoversEveryProductAndVariantCell(): void
    {
        $this->db->on('FROM licenses', []);

        $matrix = $this->service->matrix();

        self::assertSame(['app', 'php', 'theme', 'node'], $matrix['products']);
        self::assertSame(['bd', 'int'], $matrix['variants']);

        foreach ($matrix['products'] as $product) {
            self::assertArrayHasKey($product, $matrix['cells']);
            foreach ($matrix['variants'] as $variant) {
                self::assertArrayHasKey($variant, $matrix['cells'][$product], "missing {$product}/{$variant}");
            }
        }
    }

    public function testMatrixCellsCarryDrilldownLinks(): void
    {
        $this->db->on('FROM licenses', []);

        $cell = $this->service->matrix()['cells']['node']['bd'];

        self::assertSame(0, $cell['total']);
        self::assertSame(0, $cell['active']);
        self::assertArrayHasKey('offered', $cell);
        self::assertStringContainsString('product=node', $cell['licenses']);
        self::assertStringContainsString('variant=bd', $cell['licenses']);
    }

    public function testMatrixUsesTheDerivedExpressionForItsActiveTally(): void
    {
        $this->db->on('FROM licenses', []);

        $this->service->matrix();

        self::assertStringContainsString('SUM(CASE WHEN', $this->db->sql());
        self::assertStringContainsString('GROUP BY l.product, l.variant', $this->db->sql());
    }

    // ------------------------------------------------------------ active plans

    public function testActivePlanCountsOnlyIncludeEnabledPlans(): void
    {
        $this->db->on('FROM plans', [['product' => 'node', 'c' => 4]]);

        $counts = $this->service->activePlanCounts();

        self::assertSame(['app', 'php', 'theme', 'node'], array_keys($counts));
        self::assertSame(4, $counts['node']);
        self::assertStringContainsString('active = 1', $this->db->sql());
    }

    public function testVariantCountsAlwaysCoverBothVariants(): void
    {
        $this->db->on('FROM licenses', [['variant' => 'bd', 'c' => 6]]);

        $counts = $this->service->countsByVariant();

        self::assertSame(['bd', 'int'], array_keys($counts));
        self::assertSame(6, $counts['bd']);
        self::assertSame(0, $counts['int']);
    }
}