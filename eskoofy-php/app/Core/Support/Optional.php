<?php
declare(strict_types=1);

namespace App\Core\Support;

/**
 * Null-safe wrapper so Blade views can call optional($value)->foo.
 */
class Optional
{
    public mixed $value = null;

    public function __construct(mixed $value = null)
    {
        $this->value = $value;
    }

    public function __get(string $key): mixed
    {
        if (is_object($this->value) && isset($this->value->{$key})) {
            return $this->value->{$key};
        }
        if (is_array($this->value)) {
            return $this->value[$key] ?? null;
        }
        return null;
    }

    public function __isset(string $key): bool
    {
        if (is_object($this->value)) {
            return isset($this->value->{$key});
        }
        if (is_array($this->value)) {
            return array_key_exists($key, $this->value);
        }
        return false;
    }

    public function __call(string $method, array $parameters): mixed
    {
        if (is_object($this->value) && method_exists($this->value, $method)) {
            return $this->value->{$method}(...$parameters);
        }
        return null;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}