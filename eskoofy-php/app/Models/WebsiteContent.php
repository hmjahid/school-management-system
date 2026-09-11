<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class WebsiteContent extends Model
{
    protected static string $table = 'website_contents';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'page', 'title', 'title_en', 'title_bn', 'content', 'content_en',
        'content_bn', 'cms_input_mode', 'meta_description',
        'meta_description_en', 'meta_description_bn', 'meta_keywords',
        'images', 'is_active', 'settings',
    ];

    protected array $casts = [
        'content' => 'json',
        'content_en' => 'json',
        'content_bn' => 'json',
        'images' => 'json',
        'settings' => 'json',
        'is_active' => 'boolean',
    ];

    public static function getContent(string $page, array $default = []): self
    {
        $row = \App\Core\Schema::hasTable('website_contents')
            ? static::query()->where('page', $page)->first()
            : null;

        if (!$row) {
            $row = new self([
                'page'        => $page,
                'title'       => \App\Core\Support\Str::title(str_replace('-', ' ', $page)),
                'content'     => $default,
                'content_en'  => $default,
                'content_bn'  => [],
                'is_active'   => true,
            ]);
        }

        return $row->cloneForPublic($default);
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'bn') {
            $t = $this->title_bn ?? null;
            $en = $this->title_en ?? $this->title;
            if (!is_string($t) || $t === '' || (is_string($en) && trim($t) === trim($en))) {
                $t = null;
            }
        } else {
            $t = $this->title_en ?? $this->title;
        }
        if (is_string($t) && $t !== '') {
            return $t;
        }
        return \App\Core\Support\Str::title(str_replace('-', ' ', (string) $this->page));
    }

    public function localizedMetaDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $m = $locale === 'bn'
            ? ($this->meta_description_bn ?? $this->meta_description_en ?? $this->meta_description)
            : ($this->meta_description_en ?? $this->meta_description);
        return is_string($m) && $m !== '' ? $m : null;
    }

    public function localizedPayload(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $en = is_array($this->content_en) && $this->content_en !== [] ? $this->content_en : (is_array($this->content) ? $this->content : []);
        $bn = is_array($this->content_bn) ? $this->content_bn : [];
        if ($locale !== 'bn' || $bn === []) {
            return $en;
        }
        return self::stripUntranslatedLeaves($en, $bn);
    }

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
                continue;
            } elseif (is_scalar($enV) && is_scalar($bnV)) {
                $enStr = trim((string) $enV);
                $bnStr = trim((string) $bnV);
                if ($bnStr !== '' && $bnStr !== $enStr) {
                    $out[$k] = $bnV;
                }
            } elseif (is_scalar($enV) && $bnV === null) {
                $textLike = ['title', 'heading', 'intro', 'motto', 'caption', 'message', 'quote', 'cta_primary', 'cta_secondary', 'view_all', 'section_title', 'name', 'designation', 'subtitle'];
                if (!in_array($k, $textLike, true)) {
                    $out[$k] = $enV;
                }
            }
        }
        return $out;
    }

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

    public function getImageUrl(?string $path): ?string
    {
        return $path ? url('storage/' . ltrim($path, '/')) : null;
    }

    public static function getActivePages(): array
    {
        return static::query()->where('is_active', true)->pluck('page');
    }
}
