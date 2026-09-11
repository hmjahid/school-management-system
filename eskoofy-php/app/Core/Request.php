<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $_POST[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }

    public function filled(string|array $key): bool
    {
        foreach ((array) $key as $k) {
            $value = $this->get($k);
            if ($value === null || $value === '') {
                return false;
            }
        }
        return true;
    }

    public function isFilled(string|array $key): bool
    {
        return $this->filled($key);
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function hasAny(array|string $keys): bool
    {
        foreach ((array) $keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }

    public function string(string $key, mixed $default = ''): \App\Core\Support\Stringable
    {
        return new \App\Core\Support\Stringable((string) ($this->get($key, $default) ?? ''));
    }

    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public function date(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        return $value ? new \App\Core\Support\Carbon($value) : null;
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return rtrim($uri, '/') ?: '/';
    }

    public function getRequestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function url(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 0) == 443
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function isJson(): bool
    {
        return str_contains($this->contentType(), 'application/json');
    }

    public function contentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public function json(): ?array
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true);
    }

    public function is(string ...$patterns): bool
    {
        $path = $this->path();
        foreach ($patterns as $pattern) {
            $pattern = '/' . trim($pattern, '/');
            if ($pattern === $path) {
                return true;
            }
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }
        return false;
    }

    public function route(): object
    {
        return new class($this->path()) {
            public function __construct(protected string $path)
            {
            }

            public function getName(): ?string
            {
                return null;
            }

            public function uri(): string
            {
                return $this->path;
            }

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $default;
            }
        };
    }

    /**
     * Laravel-compatible request()->routeIs('dashboard.students.*') using the
     * generated name -> URI map.
     */
    public function routeIs(string ...$patterns): bool
    {
        static $map = null;
        if ($map === null) {
            $file = dirname(__DIR__, 2) . '/config/routes.php';
            $map = is_file($file) ? require $file : [];
        }
        $path = $this->path();
        foreach ($patterns as $pattern) {
            $matches = array_keys($map, $path, true);
            if ($matches === []) {
                // wildcard: match names against pattern, compare URIs
                foreach ($map as $name => $uri) {
                    if (fnmatch($pattern, (string) $name)) {
                        $regex = '#^' . str_replace(['{', '}'], ['[^/]+', ''], $uri) . '$#';
                        if (preg_match($regex, $path)) {
                            return true;
                        }
                    }
                }
                continue;
            }
            foreach ($matches as $name) {
                if (fnmatch($pattern, (string) $name)) {
                    return true;
                }
            }
        }
        return false;
    }
}
