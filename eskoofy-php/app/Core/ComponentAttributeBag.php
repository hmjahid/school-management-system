<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Stand-in for Laravel's ComponentAttributeBag so component views can use
 * {{ $attributes }}, $attributes->merge([...]), ->class([...]), ->get(), etc.
 */
class ComponentAttributeBag implements \ArrayAccess, \IteratorAggregate, \Countable, \Stringable
{
    /** @var array<string,mixed> */
    protected array $attributes = [];

    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public static function from(array $attributes): static
    {
        return new static($attributes);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function only(mixed $keys): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(array_intersect_key($this->attributes, array_flip($keys)));
    }

    public function except(mixed $keys): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(array_diff_key($this->attributes, array_flip($keys)));
    }

    public function merge(array $attributeDefaults = []): static
    {
        $merged = $this->attributes;
        if (isset($merged['class']) && isset($attributeDefaults['class'])) {
            $merged['class'] = trim($merged['class'] . ' ' . $attributeDefaults['class']);
            unset($attributeDefaults['class']);
        }
        $merged = array_merge($attributeDefaults, $merged);
        return new static($merged);
    }

    public function prepend(array $attributeDefaults = []): static
    {
        $merged = $attributeDefaults;
        foreach ($this->attributes as $key => $value) {
            if ($key === 'class' && isset($merged['class'])) {
                $merged['class'] = trim($value . ' ' . $merged['class']);
                continue;
            }
            $merged[$key] = $value;
        }
        return new static($merged);
    }

    public function class(array $classes): string
    {
        return $this->__invokeBag()->class($classes);
    }

    protected function __invokeBag(): ComponentAttributeBag
    {
        return $this;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    protected function toString(): string
    {
        $parts = [];
        foreach ($this->attributes as $key => $value) {
            if ($value === true || $value === null) {
                if ($value === true) {
                    $parts[] = $key;
                }
                continue;
            }
            if (is_array($value)) {
                $value = implode(' ', array_filter($value, fn ($v) => !is_bool($v)));
            }
            if (is_bool($value)) {
                $parts[] = $key . '="' . ($value ? '1' : '0') . '"';
                continue;
            }
            $parts[] = $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }
        return implode(' ', $parts);
    }

    // -- ArrayAccess / iterator for compatibility --------------------------

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->attributes);
    }

    public function count(): int
    {
        return count($this->attributes);
    }
}