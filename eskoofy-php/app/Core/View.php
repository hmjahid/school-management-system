<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = []): void
    {
        $data = array_merge(self::$shared, $data);
        extract($data);

        $contentPath = __DIR__ . '/../../views/' . str_replace('.', '/', $template) . '.php';
        $layout = $data['layout'] ?? 'layouts.main';
        $layoutPath = __DIR__ . '/../../views/' . str_replace('.', '/', $layout) . '.php';

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
        $path = __DIR__ . '/../../views/' . str_replace('.', '/', $partial) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
}
