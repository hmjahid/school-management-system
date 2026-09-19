<?php
declare(strict_types=1);

namespace App\Services;

/**
 * SEO helpers: canonical URLs, Open Graph / Twitter cards, hreflang and
 * JSON-LD structured data. The main layout calls render() with per-page
 * overrides supplied by views via `$seo`.
 */
class Seo
{
    public static function baseUrl(): string
    {
        $configured = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        if ($host !== '') {
            return $scheme . '://' . $host;
        }

        return $configured !== '' ? $configured : 'https://eskoofy.com';
    }

    public static function currentUrl(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);

        return self::baseUrl() . rtrim($path, '/') . ($query !== null && $query !== '' ? '?' . $query : '');
    }

    /**
     * @param array<string, mixed> $seo
     */
    public static function render(array $seo = []): void
    {
        $base = self::baseUrl();
        $title = (string) ($seo['title'] ?? '');
        $description = (string) ($seo['description'] ?? '');
        $image = (string) ($seo['image'] ?? '/icons/icon-512.png');
        $canonical = (string) ($seo['canonical'] ?? self::currentUrl());
        $ogType = (string) ($seo['type'] ?? 'website');
        $noIndex = !empty($seo['noindex']);
        $locale = I18n::current();
        $siteName = (string) \App\Models\Settings::get('site.name', 'Eskoofy');

        if (preg_match('#^https?://#', $image)) {
            $absoluteImage = $image;
        } else {
            $absoluteImage = $base . '/' . ltrim($image, '/');
        }

        if (preg_match('#^https?://#', $canonical)) {
            $canonicalFull = $canonical;
        } else {
            $canonicalFull = $base . '/' . ltrim($canonical, '/');
        }

        if ($noIndex) {
            echo '<meta name="robots" content="noindex, nofollow">';
        } else {
            echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">';
        }

        echo "\n    <link rel=\"canonical\" href=\"" . htmlspecialchars($canonicalFull) . "\">";
        echo "\n    <link rel=\"alternate\" hreflang=\"en\" href=\"" . htmlspecialchars($canonicalFull) . "\">";
        echo "\n    <link rel=\"alternate\" hreflang=\"bn\" href=\"" . htmlspecialchars($canonicalFull) . "\">";
        echo "\n    <link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($canonicalFull) . "\">";

        if ($description !== '') {
            echo "\n    <meta name=\"description\" content=\"" . htmlspecialchars($description) . "\">";
        }

        echo "\n    <meta property=\"og:site_name\" content=\"" . htmlspecialchars($siteName) . "\">";
        echo "\n    <meta property=\"og:locale\" content=\"" . htmlspecialchars($locale === 'bn' ? 'bn_BD' : 'en_US') . "\">";
        echo "\n    <meta property=\"og:type\" content=\"" . htmlspecialchars($ogType) . "\">";

        if ($title !== '') {
            echo "\n    <meta property=\"og:title\" content=\"" . htmlspecialchars($title) . "\">";
        }

        if ($description !== '') {
            echo "\n    <meta property=\"og:description\" content=\"" . htmlspecialchars($description) . "\">";
        }

        echo "\n    <meta property=\"og:url\" content=\"" . htmlspecialchars($canonicalFull) . "\">";
        echo "\n    <meta property=\"og:image\" content=\"" . htmlspecialchars($absoluteImage) . "\">";
        echo "\n    <meta name=\"twitter:card\" content=\"summary_large_image\">";

        if ($title !== '') {
            echo "\n    <meta name=\"twitter:title\" content=\"" . htmlspecialchars($title) . "\">";
        }

        if ($description !== '') {
            echo "\n    <meta name=\"twitter:description\" content=\"" . htmlspecialchars($description) . "\">";
        }

        echo "\n    <meta name=\"twitter:image\" content=\"" . htmlspecialchars($absoluteImage) . "\">";

        $schemas = (array) ($seo['schema'] ?? []);
        $schemas = array_merge([self::organization(), self::webSite(), self::webPage($title, $description)], $schemas);

        if (!empty($seo['breadcrumbs'])) {
            $schemas[] = self::breadcrumbs((array) $seo['breadcrumbs']);
        }

        foreach ($schemas as $schema) {
            if (is_array($schema)) {
                echo "\n    <script type=\"application/ld+json\">";
                echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                echo '</script>';
            }
        }

        echo "\n";
    }

    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => 'Eskoofy',
            'url'      => self::baseUrl() . '/',
            'logo'     => self::baseUrl() . '/brand/eskofy-mark.svg',
        ];
    }

    public static function webSite(): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'WebSite',
            'name'       => 'Eskoofy',
            'url'        => self::baseUrl() . '/',
            'description' => __('brand.tagline_full'),
            'inLanguage' => 'en',
        ];
    }

    public static function webPage(string $title = '', string $description = ''): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'WebPage',
            'name'     => $title !== '' ? $title : 'Eskoofy',
            'url'      => self::currentUrl(),
        ];
        if ($description !== '') {
            $schema['description'] = $description;
        }

        return $schema;
    }

    /**
     * @param array<int, array{name: string, url?: string}> $crumbs
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        $pos = 1;
        foreach ($crumbs as $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => (string) $crumb['name'],
            ];
            if (isset($crumb['url'])) {
                $item['item'] = self::baseUrl() . '/' . ltrim((string) $crumb['url'], '/');
            }
            $items[] = $item;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $product
     */
    public static function product(array $product): array
    {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => (string) $product['name'],
            'description' => (string) ($product['description'] ?? ''),
            'url'         => self::baseUrl() . '/' . ltrim((string) ($product['url'] ?? ''), '/'),
            'brand'       => ['@type' => 'Brand', 'name' => 'Eskoofy'],
            'offers'      => [
                '@type'         => 'Offer',
                'price'         => (string) ($product['price'] ?? ''),
                'priceCurrency' => (string) ($product['currency'] ?? 'USD'),
                'availability'  => 'https://schema.org/InStock',
                'url'           => self::baseUrl() . '/pricing',
            ],
        ];
    }

    /**
     * @param list<array{q: string, a: string}> $faqs
     */
    public static function faq(array $faqs): array
    {
        $items = [];
        foreach ($faqs as $f) {
            $items[] = [
                '@type'           => 'Question',
                'name'            => (string) $f['q'],
                'acceptedAnswer'  => ['@type' => 'Answer', 'text' => (string) $f['a']],
            ];
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function blogPosting(array $post): array
    {
        return [
            '@context'      => 'https://schema.org',
            '@type'         => 'BlogPosting',
            'headline'      => (string) $post['title'],
            'url'           => self::baseUrl() . '/blog/' . (string) $post['slug'],
            'description'   => (string) ($post['excerpt'] ?? ''),
            'datePublished' => (string) ($post['published_at'] ?? ''),
            'author'        => ['@type' => 'Organization', 'name' => 'Eskoofy'],
        ];
    }
}