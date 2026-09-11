<?php
declare(strict_types=1);

namespace App\Core\Support;

/**
 * Tiny Carbon-like immutable date helper so Blade views can call
 * now()->format('Y-m-d'), ->toDateString(), ->startOfMonth(), etc.
 */
class Carbon implements \Stringable
{
    protected \DateTimeInterface $dt;

    public function __construct(\DateTimeInterface|string|int|null $time = null, ?string $timezone = null)
    {
        $tz = new \DateTimeZone($timezone ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Dhaka'));
        if ($time instanceof \DateTimeInterface) {
            $this->dt = (new \DateTimeImmutable('now', $tz))->setTimestamp($time->getTimestamp());
        } elseif ($time === null || $time === '') {
            $this->dt = new \DateTimeImmutable('now', $tz);
        } elseif (is_int($time)) {
            $this->dt = (new \DateTimeImmutable('now', $tz))->setTimestamp($time);
        } else {
            $this->dt = new \DateTimeImmutable($time, $tz);
        }
    }

    public static function now(?string $tz = null): static
    {
        return new static(null, $tz);
    }

    public static function parse(mixed $time): static
    {
        return new static($time);
    }

    public static function create(int $year = 0, int $month = 1, int $day = 1, int $hour = 0, int $minute = 0, int $second = 0, ?string $timezone = null): static
    {
        $tz = new \DateTimeZone($timezone ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Dhaka'));
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second), $tz);
        return new static($dt ?? 'now', $timezone);
    }

    public static function createFromTimestamp(int|string $timestamp, ?string $timezone = null): static
    {
        return new static((int) $timestamp, $timezone);
    }

    public function format(string $format): string
    {
        return $this->dt->format($format);
    }

    public function toDateString(): string
    {
        return $this->dt->format('Y-m-d');
    }

    public function toDateTimeString(): string
    {
        return $this->dt->format('Y-m-d H:i:s');
    }

    public function toAtomString(): string
    {
        return $this->dt->format(\DateTimeInterface::ATOM);
    }

    public function toISOString(): string
    {
        return $this->dt->format('c');
    }

    public function startOfMonth(): static
    {
        return new static($this->dt->format('Y-m-01 00:00:00'));
    }

    public function endOfMonth(): static
    {
        $last = (new \DateTimeImmutable($this->dt->format('Y-m-01')))->modify('last day of this month')->format('Y-m-d 23:59:59');
        return new static($last);
    }

    public function startOfDay(): static
    {
        return new static($this->dt->format('Y-m-d 00:00:00'));
    }

    public function endOfDay(): static
    {
        return new static($this->dt->format('Y-m-d 23:59:59'));
    }

    public function startOfYear(): static
    {
        return new static($this->dt->format('Y-01-01 00:00:00'));
    }

    public function endOfYear(): static
    {
        return new static($this->dt->format('Y-12-31 23:59:59'));
    }

    public function addDays(int $days): static
    {
        $this->dt = (clone $this->dt)->modify("{$days} days");
        return $this;
    }

    public function subDays(int $days): static
    {
        return $this->addDays(-$days);
    }

    public function addMonths(int $months): static
    {
        $this->dt = (clone $this->dt)->modify("{$months} months");
        return $this;
    }

    public function subMonths(int $months): static
    {
        return $this->addMonths(-$months);
    }

    public function addYears(int $years): static
    {
        $this->dt = (clone $this->dt)->modify("{$years} years");
        return $this;
    }

    public function subYears(int $years): static
    {
        return $this->addYears(-$years);
    }

    public function copy(): static
    {
        return new static(clone $this->dt);
    }

    public function month(?int $value = null): int|static
    {
        if ($value === null) {
            return (int) $this->dt->format('n');
        }
        return new static($this->dt->format('Y') . '-' . str_pad((string) $value, 2, '0', STR_PAD_LEFT) . '-' . $this->dt->format('d H:i:s'));
    }

    public function day(?int $value = null): int|static
    {
        if ($value === null) {
            return (int) $this->dt->format('j');
        }
        return new static($this->dt->format('Y-m') . '-' . str_pad((string) $value, 2, '0', STR_PAD_LEFT) . ' ' . $this->dt->format('H:i:s'));
    }

    public function year(?int $value = null): int|static
    {
        if ($value === null) {
            return (int) $this->dt->format('Y');
        }
        return new static($value . '-' . $this->dt->format('m-d H:i:s'));
    }

    public function monthName(): string
    {
        return $this->dt->format('F');
    }

    public function addDay(): static
    {
        return $this->addDays(1);
    }

    public function subDay(): static
    {
        return $this->addDays(-1);
    }

    public function lte(mixed $other): bool
    {
        return $this->timestamp() <= $this->timestampOf($other);
    }

    public function gte(mixed $other): bool
    {
        return $this->timestamp() >= $this->timestampOf($other);
    }

    public function lt(mixed $other): bool
    {
        return $this->timestamp() < $this->timestampOf($other);
    }

    public function gt(mixed $other): bool
    {
        return $this->timestamp() > $this->timestampOf($other);
    }

    public function eq(mixed $other): bool
    {
        return $this->timestamp() === $this->timestampOf($other);
    }

    protected function timestampOf(mixed $other): int
    {
        if ($other instanceof self) {
            return $other->timestamp();
        }
        return (new static($other))->timestamp();
    }

    public function startOfWeek(): static
    {
        return new static($this->dt->modify('monday this week')->format('Y-m-d 00:00:00'));
    }

    public function endOfWeek(): static
    {
        return new static($this->dt->modify('sunday this week')->format('Y-m-d 23:59:59'));
    }

    public function startOfQuarter(): static
    {
        $q = (int) ceil((int) $this->dt->format('n') / 3);
        return new static($this->dt->format('Y') . '-' . ($q * 3 - 2) . '-01 00:00:00');
    }

    public function endOfQuarter(): static
    {
        $q = (int) ceil((int) $this->dt->format('n') / 3);
        $last = (new \DateTimeImmutable($this->dt->format('Y') . '-' . ($q * 3) . '-01'))->modify('last day of this month')->format('Y-m-d 23:59:59');
        return new static($last);
    }

    public function subMonth(): static
    {
        return $this->addMonths(-1);
    }

    public function addMonth(): static
    {
        return $this->addMonths(1);
    }

    public function diffInDays(mixed $other = null): int
    {
        $other = ($other instanceof static) ? $other->dt : new \DateTimeImmutable((string) $other);
        return (int) $this->dt->diff($other)->days;
    }

    public function timestamp(): int
    {
        return $this->dt->getTimestamp();
    }

    public function getTimestamp(): int
    {
        return $this->dt->getTimestamp();
    }

    public function toDateTime(): \DateTimeImmutable
    {
        return $this->dt;
    }

    public function isPast(): bool
    {
        return $this->dt->getTimestamp() < time();
    }

    public function isFuture(): bool
    {
        return $this->dt->getTimestamp() > time();
    }

    public function isToday(): bool
    {
        return $this->toDateString() === date('Y-m-d');
    }

    public function __toString(): string
    {
        return $this->toDateTimeString();
    }

    public function __get(string $key): mixed
    {
        return match ($key) {
            'year'      => (int) $this->dt->format('Y'),
            'month'     => (int) $this->dt->format('n'),
            'day'       => (int) $this->dt->format('j'),
            'hour'      => (int) $this->dt->format('G'),
            'minute'    => (int) $this->dt->format('i'),
            'second'    => (int) $this->dt->format('s'),
            'dayOfWeek' => (int) $this->dt->format('w'),
            default     => null,
        };
    }
}