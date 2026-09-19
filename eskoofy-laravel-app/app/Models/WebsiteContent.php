<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WebsiteContent extends Model
{
    public const INPUT_MODE_JSON = 'json';

    public const INPUT_MODE_FORM = 'form';

    protected $fillable = [
        'page',
        'title',
        'title_en',
        'title_bn',
        'content',
        'content_en',
        'content_bn',
        'cms_input_mode',
        'meta_description',
        'meta_description_en',
        'meta_description_bn',
        'meta_keywords',
        'images',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'content' => 'array',
        'content_en' => 'array',
        'content_bn' => 'array',
        'images' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (WebsiteContent $m) {
            if (is_array($m->content_en)) {
                $m->content = $m->content_en;
            } elseif (is_array($m->content) && $m->content_en === null) {
                $m->content_en = $m->content;
            }

            if ($m->title_en && ! $m->title) {
                $m->title = $m->title_en;
            }
        });
    }

    /**
     * English base content (legacy `content` column acts as fallback).
     *
     * @return array<string, mixed>
     */
    public function englishContentTree(): array
    {
        if (is_array($this->content_en) && $this->content_en !== []) {
            return $this->content_en;
        }

        if (is_array($this->content) && $this->content !== []) {
            return $this->content;
        }

        return [];
    }

    /**
     * Bengali content tree (may be partial; merged over English on the site).
     * Strips any leaf that is identical to its English counterpart — those are
     * untranslated placeholders from the seeder / migration and must NOT
     * shadow the real translation.
     *
     * @return array<string, mixed>
     */
    public function bengaliContentTree(): array
    {
        $bn = is_array($this->content_bn) ? $this->content_bn : [];
        $en = $this->englishContentTree();

        if ($bn === [] || $en === []) {
            return $bn;
        }

        return self::pruneIdentical($bn, $en);
    }

    /**
     * Walk two trees in parallel and drop any BN leaf whose value matches the
     * corresponding EN leaf. Used so untranslated CMS BN content doesn't
     * shadow the language-file translations.
     *
     * @param  array<string, mixed>  $bn
     * @param  array<string, mixed>  $en
     * @return array<string, mixed>
     */
    protected static function pruneIdentical(array $bn, array $en): array
    {
        $out = [];
        foreach ($bn as $k => $v) {
            $enV = $en[$k] ?? null;
            if (is_array($v) && is_array($enV)) {
                $sub = self::pruneIdentical($v, $enV);
                if ($sub !== []) {
                    $out[$k] = $sub;
                }
            } elseif (is_array($v)) {
                $out[$k] = $v;
            } else {
                $bnStr = is_scalar($v) ? trim((string) $v) : '';
                $enStr = is_scalar($enV) ? trim((string) $enV) : '';
                if ($bnStr !== '' && $bnStr !== $enStr) {
                    $out[$k] = $v;
                }
            }
        }

        return $out;
    }

    /**
     * Resolved page body for API / site-ui merge: English only, or BN merged over EN.
     *
     * For the BN locale, we walk the EN tree and use the BN value wherever
     * one exists *and is different from EN*. Otherwise we drop the leaf,
     * which lets views fall back to site_ui() for untranslated CMS body
     * content (the language file is the canonical Bengali source).
     *
     * @return array<string, mixed>
     */
    public function localizedPayload(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $en = $this->englishContentTree();
        $bn = is_array($this->content_bn) ? $this->content_bn : [];

        if ($locale !== 'bn' || $bn === []) {
            return $en;
        }

        return self::stripUntranslatedLeaves($en, $bn);
    }

    /**
     * Walk $en in parallel with $bn: keep a leaf only if BN has a different
     * value for it. If BN has a different value, use the BN value. Recurse
     * into arrays. The result contains only leaves that have a real BN
     * translation, so views can fall back to site_ui() for the rest.
     *
     * @param  array<string, mixed>  $en
     * @param  array<string, mixed>  $bn
     * @return array<string, mixed>
     */
    protected static function stripUntranslatedLeaves(array $en, array $bn): array
    {
        $out = [];
        foreach ($en as $k => $enV) {
            $bnV = $bn[$k] ?? null;
            if (is_array($enV) && is_array($bnV)) {
                $sub = self::stripUntranslatedLeaves($enV, $bnV);
                if ($sub !== []) {
                    $out[$k] = $sub;
                }
            } elseif (is_array($enV) && $bnV === null) {
                // No BN for this whole subtree — drop it.
                continue;
            } elseif (is_scalar($enV) && is_scalar($bnV)) {
                $enStr = trim((string) $enV);
                $bnStr = trim((string) $bnV);
                if ($bnStr !== '' && $bnStr !== $enStr) {
                    $out[$k] = $bnV;
                }
            } elseif (is_scalar($enV) && $bnV === null) {
                // Configuration/selection keys (e.g. hero_design) have no
                // translation — keep the English value so behavior persists
                // across locales. Only treat known text-like keys as missing.
                static $textLike = ['title', 'heading', 'intro', 'motto', 'caption', 'message', 'quote', 'cta_primary', 'cta_secondary', 'view_all', 'section_title', 'name', 'designation', 'subtitle'];
                if (! in_array($k, $textLike, true)) {
                    $out[$k] = $enV;
                }
            }
        }

        return $out;
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'bn') {
            $t = $this->title_bn ?? null;
            $en = $this->title_en ?? $this->title;

            if (! is_string($t) || $t === '' || (is_string($en) && trim($t) === trim($en))) {
                $t = $this->bnTitleFallback();
            }
        } else {
            $t = $this->title_en ?? $this->title;
        }

        if (is_string($t) && $t !== '') {
            return $t;
        }

        return Str::title(str_replace('-', ' ', (string) $this->page));
    }

    public function localizedMetaDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'bn') {
            $m = $this->meta_description_bn ?? null;
            $en = $this->meta_description_en ?? $this->meta_description;

            if (! is_string($m) || $m === '' || (is_string($en) && trim($m) === trim($en))) {
                $m = $this->bnMetaFallback();
            }
        } else {
            $m = $this->meta_description_en ?? $this->meta_description;
        }

        return is_string($m) && $m !== '' ? $m : null;
    }

    /**
     * Bengali title fallback from the language file. The CMS title_bn may be
     * a verbatim copy of title_en (untranslated), so we only fall back when
     * the stored value is missing or identical to the English title.
     */
    protected function bnTitleFallback(): ?string
    {
        $key = 'pages.'.$this->page.'.title_fallback_bn';
        $val = \Illuminate\Support\Arr::get(\App\Support\SiteFrontend::merged(), $key);

        return is_string($val) && $val !== '' ? $val : null;
    }

    /**
     * Bengali meta description fallback from the language file.
     */
    protected function bnMetaFallback(): ?string
    {
        $key = 'pages.'.$this->page.'.meta_fallback_bn';
        $val = \Illuminate\Support\Arr::get(\App\Support\SiteFrontend::merged(), $key);

        return is_string($val) && $val !== '' ? $val : null;
    }

    /**
     * Clone with `title`, `meta_description`, and `content` set for the active (or given) locale.
     * Public site views keep using $content->content / ->title.
     *
     * @param  array<string, mixed>  $default  Merged under resolved payload (e.g. home defaults)
     */
    public function cloneForPublic(array $default = [], ?string $locale = null): self
    {
        $m = clone $this;
        $locale = $locale ?? app()->getLocale();
        $payload = $m->localizedPayload($locale);
        $merged = array_replace_recursive($default, $payload);
        $m->setAttribute('content', $merged);
        $m->setAttribute('title', $m->localizedTitle($locale));
        $m->setAttribute('meta_description', $m->localizedMetaDescription($locale));

        return $m;
    }

    /**
     * @param  array<string, mixed>  $default
     */
    public static function getContent(string $page, array $default = []): self
    {
        if (! Schema::hasTable('website_contents')) {
            return (new self([
                'page' => $page,
                'title' => Str::title(str_replace('-', ' ', $page)),
                'content' => $default,
                'content_en' => $default,
                'content_bn' => [],
                'is_active' => true,
            ]))->cloneForPublic($default);
        }

        $row = self::query()->where('page', $page)->first();

        if (! $row) {
            return (new self([
                'page' => $page,
                'title' => Str::title(str_replace('-', ' ', $page)),
                'content' => $default,
                'content_en' => $default,
                'content_bn' => [],
                'is_active' => true,
            ]))->cloneForPublic($default);
        }

        return $row->cloneForPublic($default);
    }

    public function getImageUrl($path)
    {
        return $path ? url('storage/'.ltrim($path, '/')) : null;
    }

    public static function getActivePages()
    {
        return self::query()->where('is_active', true)
            ->pluck('page')
            ->all();
    }
}
