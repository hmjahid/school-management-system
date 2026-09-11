<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class News extends Model
{
    protected static string $table = 'news';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'title', 'slug', 'content', 'image_url', 'category', 'is_published',
        'is_event', 'published_at', 'event_date', 'event_location',
        'author_name', 'author_avatar',
    ];

    protected array $casts = [
        'is_published' => 'boolean',
        'is_event' => 'boolean',
        'published_at' => 'datetime',
        'event_date' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeEvents($query)
    {
        return $query->where('is_event', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now());
    }

    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'bn' && !empty($this->title_bn)) {
            return $this->title_bn;
        }
        return (string) $this->title;
    }
}
