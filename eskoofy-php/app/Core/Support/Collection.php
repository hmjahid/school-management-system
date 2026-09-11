<?php
declare(strict_types=1);

namespace App\Core\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

/**
 * Minimal Laravel-style Collection used by Blade views (collect() helper).
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    protected array $items;

    public function __construct(mixed $items = [])
    {
        $this->items = is_iterable($items) ? (array) $items : [$items];
    }

    public static function make(mixed $items = []): static
    {
        return new static($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return array_map(static function ($item) {
            return $item instanceof self ? $item->toArray() : $item;
        }, $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[array_key_first($this->items) ?? 0] ?? $default;
        }
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }
        return $default;
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            $keys = array_keys($this->items);
            return $keys ? $this->items[end($keys)] : $default;
        }
        $found = $default;
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                $found = $item;
            }
        }
        return $found;
    }

    public function map(callable $callback): static
    {
        $result = [];
        foreach ($this->items as $key => $item) {
            $result[$key] = $callback($item, $key);
        }
        return new static($result);
    }

    public function mapWithKeys(callable $callback): static
    {
        $result = [];
        foreach ($this->items as $key => $item) {
            $assoc = $callback($item, $key);
            if (is_array($assoc)) {
                $result[key($assoc)] = reset($assoc);
            }
        }
        return new static($result);
    }

    public function filter(?callable $callback = null): static
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function reject(callable $callback): static
    {
        return $this->filter(static fn ($item, $key) => !$callback($item, $key));
    }

    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function contains(mixed $value, mixed $key = null): bool
    {
        if ($key !== null) {
            return $this->contains(function ($item) use ($key, $value) {
                return data_get($item, $key) === $value;
            });
        }
        if ($value instanceof \Closure) {
            foreach ($this->items as $k => $v) {
                if ($value($v, $k)) {
                    return true;
                }
            }
            return false;
        }
        return in_array($value, $this->items, true);
    }

    public function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->filter(function ($item) use ($key, $operator, $value) {
            $actual = data_get($item, $key);
            return match ($operator) {
                '=', '=='  => $actual == $value,
                '===', 'is' => $actual === $value,
                '!='       => $actual != $value,
                '>'        => $actual > $value,
                '<'        => $actual < $value,
                '>='       => $actual >= $value,
                '<='       => $actual <= $value,
                'in'       => in_array($actual, (array) $value, true),
                'not in'   => !in_array($actual, (array) $value, true),
                default    => $actual == $value,
            };
        })->values();
    }

    public function pluck(mixed $value, ?string $key = null): static
    {
        $result = [];
        foreach ($this->items as $k => $item) {
            $val = data_get($item, is_array($value) ? $value : $value);
            if ($key === null) {
                $result[] = $val;
            } else {
                $result[data_get($item, $key)] = $val;
            }
        }
        return new static($result);
    }

    public function sum(mixed $callback = null): int|float
    {
        if ($callback === null) {
            return array_sum($this->items);
        }
        $total = 0;
        foreach ($this->items as $key => $item) {
            $total += is_callable($callback) ? $callback($item, $key) : data_get($item, $callback);
        }
        return $total;
    }

    public function avg(mixed $callback = null): ?float
    {
        $count = count($this->items);
        if ($count === 0) {
            return null;
        }
        return $this->sum($callback) / $count;
    }

    public function average(mixed $callback = null): ?float
    {
        return $this->avg($callback);
    }

    public function max(mixed $callback = null): mixed
    {
        $values = $callback === null ? $this->items : $this->map($callback)->all();
        return count($values) ? max($values) : null;
    }

    public function min(mixed $callback = null): mixed
    {
        $values = $callback === null ? $this->items : $this->map($callback)->all();
        return count($values) ? min($values) : null;
    }

    public function implode(string $value, ?string $glue = null): string
    {
        if ($glue === null) {
            return implode($value, $this->items);
        }
        $items = $this->map(fn ($item) => data_get($item, $value))->all();
        return implode($glue, $items);
    }

    public function join(string $glue, string $finalGlue = ''): string
    {
        $items = array_values($this->items);
        if ($finalGlue === '' || count($items) < 2) {
            return implode($glue, $items);
        }
        $last = array_pop($items);
        return implode($glue, $items) . $finalGlue . $last;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->items as $key => $item) {
            $carry = $callback($carry, $item, $key);
        }
        return $carry;
    }

    public function sortBy(mixed $callback, int $options = SORT_REGULAR, bool $descending = false): static
    {
        $results = [];
        foreach ($this->items as $key => $item) {
            $results[$key] = is_callable($callback) ? $callback($item, $key) : data_get($item, $callback);
        }
        $descending ? arsort($results, $options) : asort($results, $options);
        $sorted = [];
        foreach (array_keys($results) as $key) {
            $sorted[$key] = $this->items[$key];
        }
        return new static($sorted);
    }

    public function sortByDesc(mixed $callback, int $options = SORT_REGULAR): static
    {
        return $this->sortBy($callback, $options, true);
    }

    public function sort(): static
    {
        $items = $this->items;
        sort($items);
        return new static($items);
    }

    public function groupBy(mixed $groupBy): static
    {
        $result = [];
        foreach ($this->items as $key => $item) {
            $groupKey = is_callable($groupBy) ? $groupBy($item, $key) : data_get($item, $groupBy);
            $result[(string) $groupKey][] = $item;
        }
        return new static(array_map(static fn ($group) => new static($group), $result));
    }

    public function keysByGroup(): static
    {
        return $this->keys();
    }

    public function unique(mixed $key = null, bool $strict = false): static
    {
        $seen = [];
        $result = [];
        foreach ($this->items as $item) {
            $value = $key === null ? $item : data_get($item, $key);
            $hash = $strict ? serialize($value) : (string) $value;
            if (in_array($hash, $seen, true)) {
                continue;
            }
            $seen[] = $hash;
            $result[] = $item;
        }
        return new static($result);
    }

    public function push(mixed ...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        return $this;
    }

    public function merge(mixed $items): static
    {
        $items = $items instanceof self ? $items->all() : (array) $items;
        return new static(array_merge($this->items, $items));
    }

    public function concat(mixed $items): static
    {
        return $this->merge($items);
    }

    public function take(int $limit): static
    {
        if ($limit < 0) {
            return new static(array_slice($this->items, $limit, null, true));
        }
        return new static(array_slice($this->items, 0, $limit, true));
    }

    public function skip(int $count): static
    {
        return new static(array_slice($this->items, $count, null, true));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function chunk(int $size): static
    {
        return new static(array_map(
            static fn ($chunk) => new static($chunk),
            array_chunk($this->items, $size, true)
        ));
    }

    public function flatMap(callable $callback): static
    {
        return $this->map($callback)->collapse();
    }

    public function collapse(): static
    {
        $result = [];
        foreach ($this->items as $item) {
            if (is_array($item) || $item instanceof self) {
                foreach ($item instanceof self ? $item->all() : $item as $key => $value) {
                    $result[] = $value;
                }
            }
        }
        return new static($result);
    }

    public function get(mixed $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
    }

    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function put(mixed $key, mixed $value): static
    {
        $this->items[$key] = $value;
        return $this;
    }

    public function random(int $number = 1): mixed
    {
        $count = count($this->items);
        if ($number === 1) {
            return $this->items[array_rand($this->items)];
        }
        $keys = array_rand($this->items, min($number, $count));
        $result = [];
        foreach ((array) $keys as $k) {
            $result[] = $this->items[$k];
        }
        return $number === 1 ? $result[0] : new static($result);
    }

    public function eachSlice(...$args): static
    {
        return $this->chunk(...$args);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}