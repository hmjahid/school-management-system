<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
     *
     * @return array<string, mixed>
     */
    public function bengaliContentTree(): array
    {
        return is_array($this->content_bn) ? $this->content_bn : [];
    }

    /**
     * Resolved page body for API / site-ui merge: English only, or BN merged over EN.
     *
     * @return array<string, mixed>
     */
    public function localizedPayload(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $en = $this->englishContentTree();
        $bn = $this->bengaliContentTree();

        if ($locale === 'bn' && $bn !== []) {
            return array_replace_recursive($en, $bn);
        }

        return $en;
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'bn') {
            $t = $this->title_bn ?? $this->title_en ?? $this->title;
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
            $m = $this->meta_description_bn ?? $this->meta_description_en ?? $this->meta_description;
        } else {
            $m = $this->meta_description_en ?? $this->meta_description;
        }

        return is_string($m) && $m !== '' ? $m : null;
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
        return $path ? Storage::url($path) : null;
    }

    public static function getActivePages()
    {
        return self::query()->where('is_active', true)
            ->pluck('page')
            ->all();
    }
}
