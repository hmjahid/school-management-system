<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Stand-in for Laravel's ViewErrorBag ($errors in views) populated from the
 * session "errors" flash (array keyed by field).
 */
class ViewErrorBag
{
    /** @var array<string,array<int,string>> */
    protected array $bags = [];

    /**
     * @param array<string,string|array<int,string>> $errors
     */
    public function __construct(array $errors = [])
    {
        foreach ($errors as $key => $value) {
            $this->bags[$key] = is_array($value) ? array_values($value) : [$value];
        }
    }

    public function has(string $key): bool
    {
        return !empty($this->bags[$key]);
    }

    public function any(): bool
    {
        return count($this->bags) > 0;
    }

    public function count(): int
    {
        $total = 0;
        foreach ($this->bags as $messages) {
            $total += count($messages);
        }
        return $total;
    }

    public function first(string $key): string
    {
        return $this->bags[$key][0] ?? '';
    }

    public function get(string $key): array
    {
        return $this->bags[$key] ?? [];
    }

    public function all(): array
    {
        return array_merge([], ...array_values($this->bags));
    }

    public function keys(): array
    {
        return array_keys($this->bags);
    }
}