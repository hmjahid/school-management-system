<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static array $shared = [];

    /** When false, render() is a no-op (used by the SQL-focused integration tests). */
    public static bool $renderViews = true;

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function resolve(string $template): string
    {
        $segments = explode('/', str_replace(['.', '-'], ['/', '_'], $template));
        $base = __DIR__ . '/../../views/';

        $direct = $base . implode('/', $segments) . '.php';
        if (file_exists($direct)) {
            return $direct;
        }

        $variants = self::segmentVariants($segments);
        foreach ($variants as $variant) {
            $candidate = $base . implode('/', $variant) . '.php';
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $direct;
    }

    private static function segmentVariants(array $segments): array
    {
        $perSegment = [];
        foreach ($segments as $segment) {
            $options = [$segment];
            if (str_ends_with($segment, 's') && $segment !== 'sms' && $segment !== 'status') {
                $options[] = substr($segment, 0, -1);
            } else {
                $options[] = $segment . 's';
            }
            $perSegment[] = $options;
        }

        $result = [[]];
        foreach ($perSegment as $options) {
            $new = [];
            foreach ($result as $r) {
                foreach ($options as $opt) {
                    $new[] = array_merge($r, [$opt]);
                }
            }
            $result = $new;
        }

        return array_slice($result, 1);
    }

    public static function render(string $template, array $data = []): void
    {
        if (!self::$renderViews) {
            return;
        }
        $data = array_merge(self::$shared, $data);

        // Prefer Blade templates (Laravel parity). Fall back to legacy PHP views.
        $bladePath = \App\Core\Blade::resolvePath($template);
        if ($bladePath !== null) {
            echo \App\Core\Blade::render($template, $data);
            return;
        }

        extract($data);

        $contentPath = self::resolve($template);
        $layout = $data['layout'] ?? 'layouts.main';
        $layoutPath = __DIR__ . '/../../views/' . str_replace(['.', '-'], ['/', '_'], $layout) . '.php';

        ob_start();
        if (file_exists($contentPath)) {
            require $contentPath;
        }
        $contentHtml = ob_get_clean();

        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $contentHtml;
        }
    }

    public static function partial(string $partial, array $data = []): void
    {
        extract(array_merge(self::$shared, $data));
        $path = self::resolve($partial);
        if (file_exists($path)) {
            require $path;
        }
    }
}
