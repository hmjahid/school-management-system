<?php

declare(strict_types=1);

namespace Tests\Unit\I18n;

use Tests\TestCase;

/**
 * The en/bn locale files are the only place the site holds customer-facing
 * copy, and they are plain PHP arrays — so PHP will happily accept a missing
 * key, a duplicated key, or a translation whose `:placeholders` do not match the
 * English original. All three fail silently and ship to production.
 *
 * Phase 2 added the whole Product x Variant vocabulary (matrix, chooser,
 * product pages), so these checks are what keep the two axes from drifting
 * apart between locales.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §2
 */
class I18nKeyParityTest extends TestCase
{
    private const PLACEHOLDER = '/:([a-z_][a-z0-9_]*)/i';

    private static function langPath(string $locale): string
    {
        return dirname(__DIR__, 3) . '/lang/' . $locale . '.php';
    }

    private static function translations(string $locale): array
    {
        return require self::langPath($locale);
    }

    /**
     * Keys as they literally appear in the source, in order.
     *
     * `require` collapses a duplicated key (last one wins), so duplicates are
     * invisible in the returned array. Reading them back out of the source is
     * the only way to catch a key pasted twice with different copy.
     *
     * @return list<string>
     */
    private static function sourceKeys(string $locale): array
    {
        $source = (string) file_get_contents(self::langPath($locale));
        preg_match_all("/'([a-z0-9_.]+)'\s*=>/i", $source, $matches);

        return $matches[1];
    }

    /** @return list<string> */
    private static function placeholders(string $value): array
    {
        preg_match_all(self::PLACEHOLDER, $value, $matches);

        $found = $matches[1];
        sort($found);

        return $found;
    }

    // ----------------------------------------------------------------- parity

    public function test_every_english_key_has_a_bangla_translation(): void
    {
        $missing = array_diff(
            array_keys(self::translations('en')),
            array_keys(self::translations('bn')),
        );

        self::assertSame([], array_values($missing), 'Missing bn: ' . implode(', ', $missing));
    }

    public function test_no_bangla_key_is_orphaned(): void
    {
        $orphans = array_diff(
            array_keys(self::translations('bn')),
            array_keys(self::translations('en')),
        );

        self::assertSame([], array_values($orphans), 'Not in en: ' . implode(', ', $orphans));
    }

    // ------------------------------------------------------------- duplicates

    public function test_locale_files_contain_no_duplicate_keys(): void
    {
        foreach (['en', 'bn'] as $locale) {
            $keys = self::sourceKeys($locale);
            $duplicates = array_keys(array_filter(array_count_values($keys), static fn (int $n): bool => $n > 1));

            self::assertSame([], $duplicates, $locale . ' declares these keys twice: ' . implode(', ', $duplicates));
        }
    }

    public function test_source_key_count_matches_the_loaded_array(): void
    {
        foreach (['en', 'bn'] as $locale) {
            self::assertCount(
                count(self::sourceKeys($locale)),
                self::translations($locale),
                $locale . ' has keys the source-key scanner cannot see',
            );
        }
    }

    // ------------------------------------------------------------ placeholders

    public function test_bangla_placeholders_match_the_english_original(): void
    {
        $en = self::translations('en');
        $mismatched = [];

        foreach (self::translations('bn') as $key => $value) {
            if (!isset($en[$key]) || !is_string($value)) {
                continue;
            }

            $expected = self::placeholders((string) $en[$key]);
            $actual = self::placeholders($value);

            if ($expected !== $actual) {
                $mismatched[$key] = sprintf('en[%s] vs bn[%s]', implode(',', $expected), implode(',', $actual));
            }
        }

        self::assertSame([], $mismatched, 'Placeholder drift: ' . json_encode($mismatched, JSON_UNESCAPED_SLASHES));
    }

    // ------------------------------------------------------------------ values

    public function test_no_translation_is_blank(): void
    {
        $blank = [];

        foreach (['en', 'bn'] as $locale) {
            foreach (self::translations($locale) as $key => $value) {
                if (!is_string($value) || trim($value) === '') {
                    $blank[] = $locale . ':' . $key;
                }
            }
        }

        self::assertSame([], $blank, 'Blank translations: ' . implode(', ', $blank));
    }

    public function test_placeholders_are_well_formed_snake_case(): void
    {
        // `__()` replaces ':name' verbatim, so a camelCase or half-typed
        // placeholder (`:productName`, `:produc`) never resolves and the literal
        // ":produc" ships to the customer. Placeholders must be plain
        // snake_case tokens standing alone, separated by non-word characters.
        $bad = [];

        foreach (['en', 'bn'] as $locale) {
            foreach (self::translations($locale) as $key => $value) {
                if (!is_string($value)) {
                    continue;
                }

                if (preg_match_all('/\S:[a-zA-Z0-9_]+/', $value, $m)) {
                    foreach ($m[0] as $token) {
                        $name = substr($token, 1);
                        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                            $bad[] = $locale . ':' . $key . ' -> "' . $token . '"';
                        }
                    }
                }
            }
        }

        self::assertSame([], $bad, 'Malformed placeholders: ' . implode(', ', $bad));
    }

    // ------------------------------------------------- product/variant wording

    /**
     * The bug this whole phase exists to fix: the site called bd/int "the four
     * products/variants", which made a market build look like a sibling of a
     * deployable product. Conflated phrasing must not come back.
     */
    public function test_copy_never_conflates_products_and_variants(): void
    {
        $banned = ['product/variant', 'product / variant', 'products/variants', 'products / variants'];
        $hits = [];

        foreach (['en', 'bn'] as $locale) {
            foreach (self::translations($locale) as $key => $value) {
                if (!is_string($value)) {
                    continue;
                }

                $lower = mb_strtolower($value);
                foreach ($banned as $phrase) {
                    if (str_contains($lower, $phrase)) {
                        $hits[] = $locale . ':' . $key . ' -> "' . $value . '"';
                    }
                }
            }
        }

        self::assertSame([], $hits, 'Conflated terminology: ' . implode(' | ', $hits));
    }
}
