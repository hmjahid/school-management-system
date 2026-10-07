<?php

declare(strict_types=1);

namespace Tests\Unit\Views;

use App\Core\Database;
use App\Services\Analytics\DateRange;
use App\Services\Analytics\LicenseService;
use App\Services\Analytics\RevenueService;
use App\Services\Catalog;
use Tests\Support\AggregateDatabaseStub;
use Tests\TestCase;

/**
 * Renders the admin dashboard view end to end.
 *
 * The view is ~550 lines of PHP that no other test executes, so an undefined
 * variable or a missing controller key would only surface in a browser. This
 * renders it with representative data and turns any PHP notice/warning into a
 * failure, then asserts the Phase 1 fixes are actually visible in the markup.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.3
 */
class AdminDashboardViewTest extends TestCase
{
    private const NOW = '2026-10-04 12:00:00';

    private AggregateDatabaseStub $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new AggregateDatabaseStub();
        Database::setInstance($this->db);
    }

    /** @return array<string, mixed> exactly the keys DashboardController passes. */
    private function viewData(string $preset = '12mo', array $filters = ['product' => null, 'variant' => null]): array
    {
        $range = DateRange::fromRequest(['range' => $preset], new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC')));
        $licenses = new LicenseService($this->db);
        $revenue = new RevenueService($this->db);

        return [
            'admin'            => ['name' => 'Admin', 'email' => 'admin@eskoofy.com'],
            'stats'            => [
                'customers'            => 42,
                'licenses'             => 137,
                'active_licenses'      => 120,
                'revenue'              => 50000,
                'revenue_this_month'   => 1200,
                'revenue_prev_month'   => 900,
                'revenue_pending'      => 250,
                'unread_messages'      => 3,
                'expiring_soon'        => 2,
                'monthly_licenses'     => 90,
                'yearly_licenses'      => 47,
                'active_subscriptions' => 31,
                'mrr'                  => 1490.5,
                'arr'                  => 17886,
            ],
            'range'            => $range,
            'filters'          => $filters,
            'revenueTrend'     => $revenue->trend($range),
            'licenseByStatus'  => $licenses->countsByStatus(),
            'licenseByProduct' => $licenses->countsByProduct(),
            'licenseStatuses'  => LicenseService::STATUSES,
            'productCatalog'   => Catalog::all(),
            'reconciliation'   => $revenue->reconcile($range, $filters),
            'recentPayments'   => [],
            'recentLicenses'   => [],
            'expiringLicenses' => [],
            'unreadMessages'   => [],
        ];
    }

    /**
     * Render the view with warnings promoted to exceptions.
     *
     * @return array{0: string, 1: string} [html, rendered view text]
     */
    private function render(array $data): array
    {
        $adminTitle = 'Dashboard';

        // Undefined variables and array-to-string conversions must fail the test,
        // not scroll past as a notice.
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        ob_start();

        try {
            extract($data, EXTR_SKIP);
            include dirname(__DIR__, 3) . '/views/admin/dashboard.php';
            $html = (string) ob_get_contents();
        } catch (\Throwable $e) {
            ob_end_clean();
            restore_error_handler();

            self::fail('Dashboard view raised ' . $e::class . ': ' . $e->getMessage());

            return ['', ''];
        }

        ob_end_clean();
        restore_error_handler();

        return [$html, $adminTitle];
    }

    public function testTheViewRendersWithoutWarningsOrUndefinedVariables(): void
    {
        [$html] = $this->render($this->viewData());

        self::assertNotSame('', $html);
        self::assertGreaterThan(5000, strlen($html), 'suspiciously small render');
    }

    /**
     * B3: all four products must appear, each in its own catalog colour. The old
     * local colour map had no `node` entry, so Node.js rendered in the app's blue.
     */
    public function testAllFourProductsRenderInTheirCatalogColours(): void
    {
        [$html] = $this->render($this->viewData());

        foreach (Catalog::all() as $code => $product) {
            self::assertStringContainsString(Catalog::label($code), $html, "{$code} is missing from the dashboard");
            self::assertStringContainsString($product['color'], $html, "{$code} colour is missing");
        }

        self::assertStringContainsString('Node.js App', $html);
    }

    public function testProductBarsAreAlwaysPresentEvenWithNoLicenses(): void
    {
        // countsByProduct returns all four keys at zero, so the axis never collapses
        // to nothing on a fresh install.
        [$html] = $this->render($this->viewData());

        self::assertStringContainsString('Licenses by product', $html);
        self::assertSame(4, substr_count($html, 'rounded-full bg-slate-100 overflow-hidden'));

        // The donut, having no rows at all, falls back to its empty state.
        self::assertStringContainsString('No licenses yet.', $html);
    }

    public function testTheProductBarPercentagesStayInRange(): void
    {
        $data = $this->viewData();

        // One product holding everything; the rest at zero.
        $data['licenseByProduct'] = ['app' => 137, 'php' => 0, 'theme' => 0, 'node' => 0];

        [$html] = $this->render($data);

        self::assertStringContainsString('width:100%', $html);
        self::assertStringContainsString('width:0%', $html);

        preg_match_all('/width:(\d+)%/', $html, $m);
        foreach ($m[1] as $pct) {
            self::assertLessThanOrEqual(100, (int) $pct, 'a bar overflowed its track');
        }
    }

    /**
     * B2: the legend must only offer statuses that can actually hold rows, and
     * every one of them must render.
     */
    public function testTheStatusLegendRendersEveryDerivedStatus(): void
    {
        [$html] = $this->render($this->viewData());

        foreach (LicenseService::STATUSES as $status => $meta) {
            self::assertStringContainsString($meta['label'], $html, "status {$status} missing from the legend");
        }

        self::assertStringContainsString('Expiry is derived from the expiry date', $html);
    }

    /**
     * B5: a month with no payments must still appear on the axis as a zero bar,
     * so the chart never draws a phantom gap.
     */
    public function testTheRevenueChartPlotsEveryBucketInTheWindow(): void
    {
        $data = $this->viewData('12mo');
        self::assertCount(13, $data['revenueTrend']);

        [$html] = $this->render($data);

        foreach ($data['revenueTrend'] as $point) {
            self::assertStringContainsString(
                htmlspecialchars((string) $point['label']),
                $html,
                "bucket {$point['key']} is missing from the axis"
            );
        }
    }

    public function testTheChartHeadingNamesTheActiveWindowRatherThanAHardcodedRange(): void
    {
        [$monthly] = $this->render($this->viewData('30d'));
        self::assertStringContainsString('Last 30 days', $monthly);
        self::assertStringNotContainsString('Revenue — last 12 months', $monthly);

        [$weekly] = $this->render($this->viewData('7d'));
        self::assertStringContainsString('Last 7 days', $weekly);
    }

    /** B4: the reconciliation must be visible, not just computed. */
    public function testTheReconciliationPanelIsRendered(): void
    {
        [$html] = $this->render($this->viewData());

        self::assertStringContainsString('MRR vs collected', $html);
        // With no data at all the status is `empty`, which states the fact
        // without a meaningless gap figure.
        self::assertStringContainsString('No active subscriptions and no collected revenue', $html);
        self::assertStringNotContainsString('Gap:', $html);
    }

    public function testTheReconciliationGapIsShownOnceThereIsData(): void
    {
        $this->db->on('FROM subscriptions s', [['id' => 1, 'price' => '49.00', 'period' => 'monthly']]);
        $this->db->returnsOne(['total' => 49]);

        // A monthly window: $49 MRR against $49 collected reconciles.
        [$html] = $this->render($this->viewData('30d'));

        self::assertStringContainsString('MRR vs collected', $html);
        self::assertStringContainsString('Gap:', $html);
        self::assertStringContainsString('Collected revenue is in line with committed MRR', $html);
    }

    public function testALongWindowExpectsProportionallyMoreRevenueThanOneMonthOfMrr(): void
    {
        // $49 MRR over 12 months implies roughly $596 collected, so $49 in is a
        // shortfall and must be reported as one rather than read as healthy.
        $this->db->on('FROM subscriptions s', [['id' => 1, 'price' => '49.00', 'period' => 'monthly']]);
        $this->db->returnsOne(['total' => 49]);

        [$html] = $this->render($this->viewData('12mo'));

        self::assertStringContainsString('falls short of committed MRR', $html);
    }

    public function testTheReconciliationPanelRendersForEveryStatus(): void
    {
        // Each status maps to a different tone class; none may fatal.
        $statuses = ['reconciled', 'divergent', 'mrr_without_revenue', 'revenue_without_mrr', 'empty'];

        foreach ($statuses as $status) {
            $data = $this->viewData();
            $data['reconciliation'] = array_merge($data['reconciliation'], [
                'status' => $status,
                'gap'    => 12.5,
                'note'   => 'note for ' . $status,
            ]);

            [$html] = $this->render($data);

            self::assertStringContainsString('note for ' . $status, $html);
        }
    }

    public function testTheFilterBarExposesEveryProduct(): void
    {
        [$html] = $this->render($this->viewData());

        foreach (Catalog::keys() as $code) {
            self::assertStringContainsString('value="' . $code . '"', $html);
        }

        self::assertStringContainsString('name="range"', $html);
        self::assertStringNotContainsString('name="variant"', $html);
    }

    public function testTheActiveRangePresetIsMarkedAsSelected(): void
    {
        [$html] = $this->render($this->viewData('90d'));

        // The 90d preset link must be the one marked current, and the hidden
        // range field must carry the same value so Apply preserves it.
        self::assertStringContainsString('value="90d"', $html);
        self::assertStringContainsString('href="/admin/dashboard?range=90d"', $html);
        self::assertStringContainsString('aria-current="true"', $html);
    }

    public function testRangeLinksPreserveTheActiveFilters(): void
    {
        [$html] = $this->render($this->viewData('30d', ['product' => 'node', 'variant' => 'bd']));

        // Choosing a new range must not silently drop the product filter, and
        // the removed variant filter must not leak back into the query string.
        self::assertStringContainsString('range=7d&amp;product=node', $html);
        self::assertStringNotContainsString('variant=bd', $html);
    }

    public function testClearFiltersAppearsOnlyWhenAFilterIsActive(): void
    {
        [$unfiltered] = $this->render($this->viewData('30d'));
        self::assertStringNotContainsString('Clear filters', $unfiltered);

        [$filtered] = $this->render($this->viewData('30d', ['product' => 'node', 'variant' => null]));
        self::assertStringContainsString('Clear filters', $filtered);
    }

    public function testTheSelectedFilterIsMarkedSelected(): void
    {
        [$html] = $this->render($this->viewData('30d', ['product' => 'theme', 'variant' => 'int']));

        self::assertStringContainsString('value="theme" selected', $html);
        self::assertStringNotContainsString('value="int" selected', $html);
    }

    /**
     * I18n::t() echoes the key back when a translation is missing, so an
     * untranslated catalog entry would render `catalog.product.node` in the UI.
     */
    public function testNoRawLangKeysLeakIntoTheMarkup(): void
    {
        [$html] = $this->render($this->viewData());

        self::assertSame(0, preg_match_all('/catalog\.product\.[a-z_]+/', $html), 'catalog lang key leaked');
        self::assertSame(0, preg_match_all('/\bvariant\.[a-z_]+/', $html), 'variant lang key leaked');
    }

    public function testEmptyStatesRenderInsteadOfBlankPanels(): void
    {
        [$html] = $this->render($this->viewData());

        self::assertStringContainsString('No licenses expiring in the next 30 days', $html);
        self::assertStringContainsString('Inbox zero', $html);
    }

    public function testMarkupIsHtmlEscapedForUntrustedValues(): void
    {
        $data = $this->viewData();
        $data['expiringLicenses'] = [[
            'id'             => 1,
            'license_key'    => '<script>alert(1)</script>',
            'expires_at'     => '2026-10-20 00:00:00',
            'customer_name'  => '<img src=x onerror=alert(1)>',
            'customer_email' => '"><script>bad()</script>',
        ]];
        $data['unreadMessages'] = [[
            'name'    => '<b>bold</b>',
            'message' => '<script>alert(2)</script>',
        ]];

        [$html] = $this->render($data);

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringNotContainsString('<script>alert(2)</script>', $html);
        self::assertStringNotContainsString('<img src=x onerror', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * The charts are mounted by `public/js/charts.js` from a JSON attribute, so
     * a malformed spec would ship as a silently blank panel. Every spec that
     * renders must be valid JSON.
     */
    public function testEveryRenderedChartSpecIsValidJson(): void
    {
        [$html] = $this->render($this->viewData());

        self::assertStringContainsString('data-esk-chart="chartjs"', $html);

        preg_match_all('/data-chart-spec="([^"]*)"/', $html, $m);
        self::assertNotEmpty($m[1], 'no chart specs were rendered');

        foreach ($m[1] as $encoded) {
            $decoded = json_decode(html_entity_decode($encoded, ENT_QUOTES, 'UTF-8'), true);
            self::assertIsArray($decoded, 'a chart spec was not valid JSON');
        }
    }

    public function testTheDashboardMarksItsChartsAsImagesWithLabels(): void
    {
        [$html] = $this->render($this->viewData());

        preg_match_all('/class="esk-chart[^"]*"\s+role="img"\s+aria-label="([^"]+)"/', $html, $m);
        self::assertNotEmpty($m[1], 'a chart was mounted without an accessible label');

        foreach ($m[1] as $label) {
            self::assertNotSame('', $label);
        }
    }

    // ----------------------------------------------------- enterprise §4 panels

    /**
     * The Revenue tile becomes the hero (wider, accent wash) and the trending
     * block renders its three columns with compact numbers and signed deltas.
     */
    public function testTheHeroRevenueCardAndTrendingPanelRender(): void
    {
        $data = $this->viewData();
        $data['trending'] = [
            'plans'     => [
                ['id' => 1, 'name' => 'Pro yearly', 'product' => 'app', 'collected' => 1200.0, 'prior' => 900.0, 'delta_pct' => 33.3],
            ],
            'countries' => [
                ['country' => 'Germany', 'visits' => 120, 'prior' => 60, 'delta_pct' => 100.0],
            ],
            'gateway'   => ['gateway' => 'stripe', 'current' => 500.0, 'prior' => 250.0, 'delta_pct' => 100.0],
        ];

        [$html] = $this->render($data);

        self::assertStringContainsString('esk-stat--hero', $html);
        self::assertStringContainsString('trending', $html);
        self::assertStringContainsString('Pro yearly', $html);
        self::assertStringContainsString('Germany', $html);
        self::assertStringContainsString('Stripe', $html);

        // Compact feet + dense numbers on the trending rows; deltas signed.
        self::assertStringContainsString('$1.2k', $html);
        self::assertStringContainsString('+33.3%', $html);
        self::assertStringContainsString('120', $html);

        // Every chart spec is still valid JSON with the new panels mounted.
        preg_match_all('/data-chart-spec="([^"]*)"/', $html, $m);
        foreach ($m[1] as $encoded) {
            $decoded = json_decode(html_entity_decode($encoded, ENT_QUOTES, 'UTF-8'), true);
            self::assertIsArray($decoded, 'a chart spec was not valid JSON with trending mounted');
        }
    }

    /**
     * The ARR gauge is opt-in: with a blank target the card degrades to plain
     * MRR/ARR numbers; with one it draws the radial arc of progress.
     */
    public function testTheArrGaugeRendersWhenATargetIsSetAndDegradesWithout(): void
    {
        [$without] = $this->render($this->viewData());

        self::assertStringContainsString('Recurring revenue', $without);
        self::assertStringNotContainsString('ARR vs target', $without);
        self::assertStringNotContainsString('ARR progress', $without);
        self::assertStringContainsString('$17,886', $without, 'mrr/arr degrade path still shows the numbers');

        $data = $this->viewData();
        $data['arrTarget'] = '120000';

        [$with] = $this->render($data);

        self::assertStringContainsString('ARR vs target', $with);
        self::assertStringContainsString('ARR progress', $with);
        self::assertStringContainsString('radialBar', $with);
        self::assertStringContainsString('Target', $with);
        self::assertStringContainsString('$120,000', $with);
    }

    /**
     * A brand-new store (no customers, licenses, subscriptions or revenue)
     * swaps the chart panels for one focused onboarding checklist, while the
     * hero KPIs and quick actions stay.
     */
    public function testAFreshInstallShowsTheOnboardingChecklistInsteadOfEmptyPanels(): void
    {
        $data = $this->viewData();
        $data['stats'] = array_merge($data['stats'], [
            'customers'            => 0,
            'licenses'             => 0,
            'active_subscriptions' => 0,
            'revenue'              => 0,
            'revenue_this_month'   => 0,
            'revenue_pending'      => 0,
            'mrr'                  => 0,
            'arr'                  => 0,
        ]);

        [$html] = $this->render($data);

        self::assertStringContainsString('Get your first license out the door', $html);
        self::assertStringContainsString('esk-onboard', $html);
        self::assertStringContainsString('Issue your first license', $html);
        self::assertStringContainsString('Create a plan', $html);
        self::assertStringContainsString('Configure a gateway', $html);

        // The sea of empty charts is gone…
        self::assertStringNotContainsString('Collected revenue —', $html);
        self::assertStringNotContainsString('Renewal-risk heatmap', $html);
        self::assertStringNotContainsString('What&rsquo;s trending', $html);

        // …but the operational surface is still there.
        self::assertStringContainsString('Quick actions', $html);
        self::assertStringContainsString('esk-stat--hero', $html);
    }
}