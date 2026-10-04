<?php
declare(strict_types=1);

namespace App\Services\Analytics;

/**
 * An immutable reporting window with a sensible bucket size.
 *
 * Fixes the class of bug where a chart's x-axis and its SQL window disagree —
 * the old dashboard anchored a "last 12 months" query to the first of the month
 * (`DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-01'), INTERVAL 11 MONTH)`), which can drop
 * a partial month at the left edge. This type computes a *rolling* window and
 * buckets in PHP, so the series is always dense and always matches the label.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.2 (B5)
 */
final class DateRange
{
    public const BUCKET_HOUR  = 'hour';
    public const BUCKET_DAY   = 'day';
    public const BUCKET_WEEK  = 'week';
    public const BUCKET_MONTH = 'month';

    public const PRESETS = ['today', '7d', '30d', '90d', '12mo', 'ytd', 'all'];

    private function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly string $bucket,
        public readonly string $preset,
    ) {
    }

    /**
     * Build a range from a preset name. Unknown presets fall back to 30d.
     */
    public static function preset(string $preset, ?\DateTimeImmutable $now = null): self
    {
        $now = $now ?? new \DateTimeImmutable('now');
        $preset = strtolower(trim($preset));

        return match ($preset) {
            'today' => new self(
                $now->setTime(0, 0),
                $now,
                self::BUCKET_HOUR,
                $preset
            ),
            '7d' => new self(
                $now->modify('-6 days')->setTime(0, 0),
                $now,
                self::BUCKET_DAY,
                $preset
            ),
            '90d' => new self(
                $now->modify('-89 days')->setTime(0, 0),
                $now,
                self::BUCKET_WEEK,
                $preset
            ),
            '12mo' => new self(
                $now->modify('-1 year')->setTime(0, 0),
                $now,
                self::BUCKET_MONTH,
                $preset
            ),
            'ytd' => new self(
                $now->setDate((int) $now->format('Y'), 1, 1)->setTime(0, 0),
                $now,
                self::BUCKET_MONTH,
                $preset
            ),
            'all' => new self(
                new \DateTimeImmutable('2000-01-01 00:00:00'),
                $now,
                self::BUCKET_MONTH,
                $preset
            ),
            default => new self(
                $now->modify('-29 days')->setTime(0, 0),
                $now,
                self::BUCKET_DAY,
                '30d'
            ),
        };
    }

    /**
     * Build a range from explicit bounds, auto-selecting the bucket from the span.
     */
    public static function between(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        ?string $bucket = null,
    ): self {
        $f = \DateTimeImmutable::createFromInterface($from);
        $t = \DateTimeImmutable::createFromInterface($to);

        if ($f > $t) {
            [$f, $t] = [$t, $f];
        }

        return new self($f, $t, $bucket ?? self::bucketForDays(self::spanOf($f, $t)), 'custom');
    }

    /** Parse `?from=&to=`, falling back to the preset. */
    public static function fromRequest(array $query, ?\DateTimeImmutable $now = null): self
    {
        $now = $now ?? new \DateTimeImmutable('now');
        $preset = (string) ($query['range'] ?? '30d');
        $range = self::preset($preset, $now);

        if (! empty($query['from']) && ! empty($query['to'])) {
            try {
                return self::between(
                    new \DateTimeImmutable((string) $query['from']),
                    new \DateTimeImmutable((string) $query['to'] . ' 23:59:59'),
                );
            } catch (\Exception) {
                // Malformed input falls back to the preset rather than erroring.
            }
        }

        return $range;
    }

    /** The window immediately before this one, of equal length — for deltas. */
    public function prior(): self
    {
        $days = max(1, (int) floor($this->spanInDays()));
        $priorTo = $this->from->modify('-1 second');
        $priorFrom = $priorTo->modify('-' . ($days - 1) . ' days');

        return new self($priorFrom, $priorTo, $this->bucket, 'prior');
    }

    /**
     * Build an inclusive WHERE fragment for a `DATETIME` column, appending its
     * placeholders to `$params` so call sites stay in binding order.
     *
     *     $params = [];
     *     $where = 'status = ? AND ' . $range->where('paid_at', $params);
     *     $params[] = 'paid';
     */
    public function where(string $column, array &$params): string
    {
        $params[] = $this->from->format('Y-m-d H:i:s');
        $params[] = $this->to->format('Y-m-d H:i:s');

        return "{$column} >= ? AND {$column} <= ?";
    }

    /** @return list<string> placeholders in the order {@see where()} emits them */
    public function sqlParams(): array
    {
        return [$this->from->format('Y-m-d H:i:s'), $this->to->format('Y-m-d H:i:s')];
    }

    /** MySQL `DATE_FORMAT` expression matching {@see keyFormat()}. */
    public function sqlBucketExpression(string $column): string
    {
        return match ($this->bucket) {
            self::BUCKET_HOUR  => "DATE_FORMAT({$column}, '%Y-%m-%d %H')",
            self::BUCKET_DAY   => "DATE({$column})",
            self::BUCKET_WEEK  => "DATE_FORMAT({$column}, '%x-W%v')",
            self::BUCKET_MONTH => "DATE_FORMAT({$column}, '%Y-%m')",
            default            => "DATE({$column})",
        };
    }

    /**
     * Every bucket boundary in the window, oldest first. Drives the zero-fill so
     * a chart never shows a phantom gap for a month with no sales.
     *
     * @return list<\DateTimeImmutable>
     */
    public function buckets(): array
    {
        $out = [];
        $cursor = $this->bucketStart($this->from);

        // Guard against a pathological 'all' range producing an unbounded loop.
        $max = 4096;

        while ($cursor <= $this->to && count($out) < $max) {
            $out[] = $cursor;
            $cursor = $this->advance($cursor);
        }

        return $out;
    }

    /**
     * The canonical key a row's timestamp belongs in, matching {@see buckets()}.
     */
    public function bucketKey(string|\DateTimeInterface $when): string
    {
        $when = $when instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($when)
            : new \DateTimeImmutable($when);

        return $this->bucketStart($when)->format($this->keyFormat());
    }

    /** Short axis label for a bucket boundary. */
    public function bucketLabel(\DateTimeInterface $when): string
    {
        return match ($this->bucket) {
            self::BUCKET_HOUR  => $when->format('H:i'),
            self::BUCKET_DAY   => $when->format('j M'),
            self::BUCKET_WEEK  => $when->format('j M'),
            self::BUCKET_MONTH => $when->format('M y'),
            default            => $when->format('j M'),
        };
    }

    public function keyFormat(): string
    {
        return match ($this->bucket) {
            self::BUCKET_HOUR  => 'Y-m-d H',
            self::BUCKET_DAY   => 'Y-m-d',
            self::BUCKET_WEEK  => 'o-\WW',
            self::BUCKET_MONTH => 'Y-m',
            default            => 'Y-m-d',
        };
    }

    public function spanInDays(): float
    {
        return ($this->to->getTimestamp() - $this->from->getTimestamp()) / 86400;
    }

    public function contains(string|\DateTimeInterface $when): bool
    {
        $ts = $when instanceof \DateTimeInterface
            ? $when->getTimestamp()
            : (new \DateTimeImmutable($when))->getTimestamp();

        return $ts >= $this->from->getTimestamp() && $ts <= $this->to->getTimestamp();
    }

    /** Human label for the active window, printed in every panel header. */
    public function label(): string
    {
        return match ($this->preset) {
            'today' => 'Today',
            '7d'    => 'Last 7 days',
            '30d'   => 'Last 30 days',
            '90d'   => 'Last 90 days',
            '12mo'  => 'Last 12 months',
            'ytd'   => 'Year to date',
            'all'   => 'All time',
            default => $this->from->format('j M Y') . ' – ' . $this->to->format('j M Y'),
        };
    }

    public function isAllTime(): bool
    {
        return $this->preset === 'all';
    }

    /** Cache-key fragment — stable for a given window. */
    public function cacheKey(): string
    {
        return $this->from->format('YmdHi') . '-' . $this->to->format('YmdHi') . '-' . $this->bucket;
    }

    /** Choose a bucket that yields a readable number of points. */
    public static function bucketForDays(float $days): string
    {
        return match (true) {
            $days <= 2       => self::BUCKET_HOUR,
            $days <= 120     => self::BUCKET_DAY,
            $days <= 400     => self::BUCKET_WEEK,
            default          => self::BUCKET_MONTH,
        };
    }

    /** Span in days between two points, always >= 0. */
    public static function spanOf(\DateTimeInterface $a, \DateTimeInterface $b): float
    {
        $diff = abs($a->getTimestamp() - $b->getTimestamp());

        return $diff / 86400;
    }

    private function bucketStart(\DateTimeImmutable $when): \DateTimeImmutable
    {
        return match ($this->bucket) {
            self::BUCKET_HOUR  => $when->setTime((int) $when->format('H'), 0),
            self::BUCKET_DAY   => $when->setTime(0, 0),
            self::BUCKET_WEEK  => $when->modify('monday this week')->setTime(0, 0),
            self::BUCKET_MONTH => $when->setDate((int) $when->format('Y'), (int) $when->format('n'), 1)->setTime(0, 0),
            default            => $when->setTime(0, 0),
        };
    }

    private function advance(\DateTimeImmutable $cursor): \DateTimeImmutable
    {
        return match ($this->bucket) {
            self::BUCKET_HOUR  => $cursor->modify('+1 hour'),
            self::BUCKET_DAY   => $cursor->modify('+1 day'),
            self::BUCKET_WEEK  => $cursor->modify('+1 week'),
            self::BUCKET_MONTH => $cursor->modify('first day of next month'),
            default            => $cursor->modify('+1 day'),
        };
    }
}