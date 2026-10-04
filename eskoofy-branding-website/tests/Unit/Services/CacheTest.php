<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Cache;
use Tests\TestCase;

/**
 * The file-backed cache backing the dashboard widgets.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.2
 */
class CacheTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/esk-cache-' . bin2hex(random_bytes(6));
        Cache::setDirectory($this->dir);
    }

    protected function tearDown(): void
    {
        Cache::forget('kpi');
        Cache::forget('revenue');
        Cache::setDirectory(null);

        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    // ------------------------------------------------------------ round trip

    public function testAValueSurvivesARoundTrip(): void
    {
        Cache::put('kpi', 'licenses', ['active' => 120]);

        Cache::flushMemo();

        self::assertSame(['active' => 120], Cache::get('kpi', 'licenses'));
    }

    public function testAMissReturnsNull(): void
    {
        self::assertNull(Cache::get('kpi', 'never-written'));
        self::assertFalse(Cache::has('kpi', 'never-written'));
    }

    public function testGroupsAreIsolatedFromEachOther(): void
    {
        Cache::put('kpi', 'shared', 'from-kpi');
        Cache::put('revenue', 'shared', 'from-revenue');

        Cache::flushMemo();

        self::assertSame('from-kpi', Cache::get('kpi', 'shared'));
        self::assertSame('from-revenue', Cache::get('revenue', 'shared'));
    }

    public function testAwkwardKeysDoNotEscapeTheCacheDirectory(): void
    {
        // The key reaches the filesystem via sha1(), but the group does not.
        Cache::put('../../etc', 'passwd', 'x');

        Cache::flushMemo();

        self::assertSame('x', Cache::get('../../etc', 'passwd'));

        foreach (glob($this->dir . '/*') ?: [] as $file) {
            self::assertStringStartsWith($this->dir, $file, 'cache file escaped the cache directory');
        }
    }

    public function testScalarsZeroAndNullishValuesAllRoundTrip(): void
    {
        Cache::put('kpi', 'zero', 0);
        Cache::put('kpi', 'false', false);
        Cache::put('kpi', 'empty-string', '');
        Cache::put('kpi', 'float', 1.5);

        Cache::flushMemo();

        self::assertSame(0, Cache::get('kpi', 'zero'));
        self::assertFalse(Cache::get('kpi', 'false'));
        self::assertSame('', Cache::get('kpi', 'empty-string'));
        self::assertSame(1.5, Cache::get('kpi', 'float'));
    }

    // -------------------------------------------------------------- remember

    public function testRememberComputesOnceThenServesFromCache(): void
    {
        $calls = 0;
        $producer = static function () use (&$calls) {
            $calls++;

            return ['value' => $calls];
        };

        self::assertSame(['value' => 1], Cache::remember('revenue', 'trend', $producer));
        self::assertSame(['value' => 1], Cache::remember('revenue', 'trend', $producer));
        self::assertSame(1, $calls, 'producer ran more than once');
    }

    public function testRememberServesFromTheInRequestMemoWithoutTouchingDisk(): void
    {
        $calls = 0;
        $producer = static function () use (&$calls) {
            return ++$calls;
        };

        Cache::remember('kpi', 'memo', $producer);
        Cache::remember('kpi', 'memo', $producer);

        self::assertSame(1, $calls);
    }

    public function testRememberReturnsTheComputedValueEvenWhenCachingIsUnavailable(): void
    {
        Cache::setDirectory('/proc/definitely-not-writable');

        $value = Cache::remember('kpi', 'unwritable', static fn () => ['computed' => true]);

        // Cache is an optimisation; a dashboard must still render without it.
        self::assertSame(['computed' => true], $value);
    }

    // ------------------------------------------------------------------ TTL

    public function testANonPositiveTtlDisablesTheWriteEntirely(): void
    {
        Cache::put('kpi', 'no-cache', 'value', 0);

        // Not memoised and not persisted — otherwise a "don't cache" write would
        // still be served for the rest of the request.
        self::assertNull(Cache::get('kpi', 'no-cache'));
        self::assertSame([], glob($this->dir . '/*.json') ?: [], 'nothing should hit disk');
    }

    public function testANegativeTtlIsAlsoTreatedAsNoCaching(): void
    {
        Cache::put('kpi', 'negative', 'value', -1);

        self::assertNull(Cache::get('kpi', 'negative'));
    }

    public function testAnExplicitlyExpiredEntryIsNotReturned(): void
    {
        // A 1-second TTL written a moment ago must already read back as absent
        // once its window closes; assert the boundary without sleeping on it.
        Cache::put('kpi', 'boundary', 'value', 1);
        self::assertSame('value', Cache::get('kpi', 'boundary'));

        $file = $this->dir . '/kpi-' . sha1('boundary') . '.json';
        file_put_contents($file, json_encode(['x' => time() - 1, 'v' => 'value']));
        Cache::flushMemo();

        self::assertNull(Cache::get('kpi', 'boundary'));
    }

    public function testEveryDeclaredGroupHasAPositiveTtl(): void
    {
        foreach (Cache::TTL as $group => $ttl) {
            self::assertGreaterThan(0, $ttl, "group {$group} has a non-positive TTL");
        }
    }

    public function testUnknownGroupsFallBackToTheDefaultTtl(): void
    {
        self::assertSame(Cache::DEFAULT_TTL, Cache::ttlFor('not-a-real-group'));
        self::assertSame(Cache::TTL['kpi'], Cache::ttlFor('kpi'));
    }

    public function testAnExplicitTtlOverridesTheGroupDefault(): void
    {
        Cache::put('nav', 'short', 'v', 1);

        Cache::flushMemo();

        self::assertSame('v', Cache::get('nav', 'short'));
    }

    // -------------------------------------------------------------- invalidation

    public function testForgetRemovesOneKeyWithoutTouchingSiblings(): void
    {
        Cache::put('kpi', 'a', 1);
        Cache::put('kpi', 'b', 2);

        Cache::forget('kpi', 'a');

        Cache::flushMemo();

        self::assertNull(Cache::get('kpi', 'a'));
        self::assertSame(2, Cache::get('kpi', 'b'));
    }

    public function testForgetWithoutAKeyClearsTheWholeGroup(): void
    {
        Cache::put('revenue', 'a', 1);
        Cache::put('revenue', 'b', 2);

        Cache::forget('revenue');

        Cache::flushMemo();

        self::assertNull(Cache::get('revenue', 'a'));
        self::assertNull(Cache::get('revenue', 'b'));
    }

    public function testForgetOnAnAbsentKeyIsHarmless(): void
    {
        Cache::forget('kpi', 'never-existed');

        self::assertFalse(Cache::has('kpi', 'never-existed'));
    }

    // ----------------------------------------------------------------- prune

    public function testPruneRemovesExpiredFilesAndKeepsLiveOnes(): void
    {
        Cache::put('kpi', 'live', 'v', 3600);
        self::assertDirectoryExists($this->dir);

        // Write an already-expired payload directly, as a stale file would be.
        $stale = $this->dir . '/kpi-' . sha1('stale') . '.json';
        file_put_contents($stale, json_encode(['x' => time() - 10, 'v' => 'old']));

        $removed = Cache::prune();

        self::assertSame(1, $removed);
        self::assertFileDoesNotExist($stale);

        Cache::flushMemo();
        self::assertSame('v', Cache::get('kpi', 'live'));
    }

    public function testPruneTreatsCorruptFilesAsExpiredRatherThanThrowing(): void
    {
        Cache::put('kpi', 'seed', 'v', 3600);
        file_put_contents($this->dir . '/kpi-' . sha1('corrupt') . '.json', 'not json at all');

        self::assertSame(1, Cache::prune());
    }

    public function testPruneOnAMissingDirectoryIsHarmless(): void
    {
        Cache::setDirectory($this->dir . '/never-created');

        self::assertSame(0, Cache::prune());
    }

    public function testPruneOnAnEmptyCacheIsANoop(): void
    {
        self::assertSame(0, Cache::prune());
    }

    // ------------------------------------------------------------ availability

    public function testTheCacheDirectoryIsCreatedOnDemand(): void
    {
        self::assertDirectoryDoesNotExist($this->dir);

        Cache::put('kpi', 'creates-dir', 'v');

        self::assertDirectoryExists($this->dir);
        self::assertTrue(Cache::isAvailable());
    }

    public function testNoTemporaryFilesAreLeftBehindByAtomicWrites(): void
    {
        Cache::put('kpi', 'atomic', 'v');

        self::assertSame([], glob($this->dir . '/*.tmp') ?: [], 'a temp file was left behind');
        self::assertCount(1, glob($this->dir . '/*.json') ?: []);
    }
}