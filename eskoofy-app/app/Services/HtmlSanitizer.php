<?php

namespace App\Services;

use DOMDocument;
use DOMElement;

/**
 * Allow-list HTML sanitizer for rich-text content that is later rendered
 * with `{!! !!}` (news bodies, notification/email templates). Removes any
 * script/event-handler/iframe content while keeping safe structural markup.
 */
class HtmlSanitizer
{
    /** @var array<string, array<int, string>> tag => allowed attributes */
    protected const ALLOWED_TAGS = [
        'p' => ['class'], 'br' => [], 'hr' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'ul' => [], 'ol' => [], 'li' => [],
        'h1' => [], 'h2' => ['class'], 'h3' => ['class'], 'h4' => [],
        'blockquote' => ['class'], 'pre' => ['class'], 'code' => ['class'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
        'span' => ['class'], 'div' => ['class'],
        'table' => ['class'], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['class'], 'td' => ['class', 'colspan', 'rowspan'],
        'sub' => [], 'sup' => [],
    ];

    public function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        if (! class_exists(DOMDocument::class)) {
            return strip_tags($html, '<p><br><strong><em><a><ul><ol><li><h1><h2><h3><h4><blockquote><pre><code><img><table><tr><td><th>');
        }

        // Drop comments; use a synthetic root so fragments that start with a void
        // element (e.g. <img>) survive parsing with LIBXML_HTML_NOIMPLIED.
        $html = (string) preg_replace('/<!--.*?-->/s', '', $html);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><esk-root>'.$html.'</esk-root>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementsByTagName('esk-root')->item(0);
        if ($root instanceof DOMElement) {
            $this->walk($root);
            $out = '';
            foreach ($root->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }
        } else {
            $this->walk($doc);
            $out = $doc->saveHTML($doc->documentElement);
        }

        return trim((string) preg_replace('/^<\?xml encoding="UTF-8">/', '', (string) $out));
    }

    protected function walk(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! isset(self::ALLOWED_TAGS[$tag])) {
                    // Keep text children, drop the unsafe element itself.
                    $parent = $child->parentNode;
                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }
                    $parent->removeChild($child);

                    continue;
                }

                $allowedAttrs = self::ALLOWED_TAGS[$tag];
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->nodeName);
                    $value = $attr->nodeValue;
                    if (! in_array($name, $allowedAttrs, true) || $this->isUnsafeValue($name, $value)) {
                        $child->removeAttribute($attr->nodeName);

                        continue;
                    }
                    if ($name === 'href' && ! $this->isSafeUrl($value)) {
                        $child->removeAttribute($attr->nodeName);
                    }
                }
                $this->walk($child);
            }
        }
    }

    protected function isUnsafeValue(string $attr, string $value): bool
    {
        $value = strtolower(trim($value));

        return str_starts_with($value, 'javascript:')
            || str_starts_with($value, 'data:text/html')
            || str_contains($value, 'onload')
            || str_contains($value, 'onclick')
            || str_contains($value, 'onerror')
            || str_contains($value, 'onmouseover');
    }

    protected function isSafeUrl(string $url): bool
    {
        $url = strtolower(trim($url));
        if ($url === '' || str_starts_with($url, '/')) {
            return true;
        }

        return str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, 'tel:');
    }
}
