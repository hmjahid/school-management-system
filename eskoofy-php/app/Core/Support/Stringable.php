<?php
declare(strict_types=1);

namespace App\Core\Support;

/**
 * Fluent string wrapper (str() helper) — minimal Laravel-style Stringable.
 */
class Stringable implements \JsonSerializable, \Stringable
{
    protected string $value;

    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function trim(string $charMask = " \t\n\r\0\x0B"): static
    {
        return new static(trim($this->value, $charMask));
    }

    public function upper(): static
    {
        return new static(Str::upper($this->value));
    }

    public function lower(): static
    {
        return new static(Str::lower($this->value));
    }

    public function title(): static
    {
        return new static(Str::title($this->value));
    }

    public function limit(int $limit = 100, string $end = '...'): static
    {
        return new static(Str::limit($this->value, $limit, $end));
    }

    public function slug(string $separator = '-'): static
    {
        return new static(Str::slug($this->value, $separator));
    }

    public function substr(int $start, ?int $length = null): static
    {
        return new static(Str::substr($this->value, $start, $length));
    }

    public function replace(array|string $search, array|string $replace): static
    {
        return new static(Str::replace($search, $replace, $this->value));
    }

    public function startsWith(array|string $needles): bool
    {
        return Str::startsWith($this->value, $needles);
    }

    public function endsWith(array|string $needles): bool
    {
        return Str::endsWith($this->value, $needles);
    }

    public function contains(array|string $needles): bool
    {
        return Str::contains($this->value, $needles);
    }

    public function isNotEmpty(): bool
    {
        return $this->value !== '';
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}