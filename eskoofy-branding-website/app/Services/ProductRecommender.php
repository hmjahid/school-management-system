<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Deterministic product-suggestion engine for the marketing site.
 *
 * Answers a short quiz with the best-fit Eskoofy variant out of the four
 * products/variants the monorepo ships: the Laravel app, the raw-PHP rewrite,
 * the WordPress theme and the Node.js variant.
 */
class ProductRecommender
{
    /** @var list<array{key: string, label_key: string, desc_key: string, link: string}> */
    private const CANDIDATES = [
        ['key' => 'app', 'label_key' => 'choose.result.app_name', 'desc_key' => 'choose.result.app_desc', 'link' => '/products/app'],
        ['key' => 'php', 'label_key' => 'choose.result.php_name', 'desc_key' => 'choose.result.php_desc', 'link' => '/products/php'],
        ['key' => 'theme', 'label_key' => 'choose.result.theme_name', 'desc_key' => 'choose.result.theme_desc', 'link' => '/products/theme'],
        ['key' => 'node', 'label_key' => 'choose.result.node_name', 'desc_key' => 'choose.result.node_desc', 'link' => '/custom-order?product=node'],
    ];

    /** All four products/variants with translated label/description keys. */
    public static function candidates(): array
    {
        return self::CANDIDATES;
    }

    /** @return list<string> */
    public static function productKeys(): array
    {
        return array_column(self::CANDIDATES, 'key');
    }

    /**
     * Pick the best product for the given quiz answers.
     *
     * Answers (all optional, best-effort):
     *   hosting   = wordpress | shared | vps | cloud
     *   comfort   = non_technical | some | technical
     *   stack     = any | javascript | php
     *   priority  = budget | features | control
     *   size      = small | medium | large
     *
     * @param array<string, mixed> $answers
     * @return array{product: string, runnerUp: string, score: array<string, int>, reason_key: string}
     */
    public static function recommend(array $answers): array
    {
        $hosting = self::norm($answers['hosting'] ?? '');
        $comfort = self::norm($answers['comfort'] ?? '');
        $stack = self::norm($answers['stack'] ?? '');
        $priority = self::norm($answers['priority'] ?? '');
        $size = self::norm($answers['size'] ?? '');

        $score = ['app' => 0, 'php' => 0, 'theme' => 0, 'node' => 0];

        // Hosting is the strongest signal.
        if ($hosting === 'wordpress') {
            $score['theme'] += 6;
        } elseif ($hosting === 'shared') {
            $score['php'] += 5;
            $score['app'] += 1;
        } elseif ($hosting === 'vps' || $hosting === 'cloud') {
            $score['app'] += 4;
            $score['node'] += 2;
        }

        // Stack preference is the deciding signal for JS teams — the Node
        // variant must outrank the full app even on feature-first requests.
        if ($stack === 'javascript') {
            $score['node'] += 8;
        } elseif ($stack === 'php') {
            $score['app'] += 1;
            $score['php'] += 1;
        }

        // Technical comfort: non-technical visitors are steered away from
        // anything they would have to operate themselves.
        if ($comfort === 'non_technical') {
            $score['theme'] += 2;
            $score['app'] += 2;
            $score['php'] -= 1;
            $score['node'] -= 1;
        } elseif ($comfort === 'technical') {
            $score['php'] += 1;
            $score['node'] += 1;
            $score['app'] += 1;
        }

        // Priority.
        if ($priority === 'budget') {
            $score['php'] += 3;
            $score['theme'] += 2;
            $score['app'] -= 1;
        } elseif ($priority === 'features') {
            $score['app'] += 3;
        } elseif ($priority === 'control') {
            $score['app'] += 2;
            $score['node'] += 1;
            $score['php'] += 1;
        }

        // Size: bigger schools lean to the full app.
        if ($size === 'large' || $size === 'medium') {
            $score['app'] += 2;
        }

        // Stable ordering: highest score first, then the canonical order.
        $order = ['app', 'php', 'theme', 'node'];
        $rank = ['app' => 0, 'php' => 1, 'theme' => 2, 'node' => 3];
        usort($order, static fn (string $a, string $b): int =>
            ($score[$b] <=> $score[$a]) ?: ($rank[$a] <=> $rank[$b]));

        $product = $order[0];
        $runnerUp = $order[1];

        return [
            'product' => $product,
            'runnerUp' => $runnerUp,
            'score' => $score,
            'reason_key' => self::reasonKey($answers, $product),
        ];
    }

    /** @param array<string, mixed> $answers */
    private static function reasonKey(array $answers, string $product): string
    {
        $hosting = self::norm($answers['hosting'] ?? '');
        $stack = self::norm($answers['stack'] ?? '');
        $priority = self::norm($answers['priority'] ?? '');

        if ($hosting === 'wordpress') {
            return 'choose.reason.wordpress';
        }
        if ($hosting === 'shared') {
            return 'choose.reason.shared';
        }
        if ($stack === 'javascript') {
            return 'choose.reason.javascript';
        }
        if ($priority === 'budget') {
            return 'choose.reason.budget';
        }
        if ($priority === 'features') {
            return 'choose.reason.features';
        }
        if ($priority === 'control') {
            return 'choose.reason.control';
        }

        return 'choose.reason.' . $product;
    }

    private static function norm(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }
}
