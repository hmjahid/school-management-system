<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Announcement extends Model
{
    protected static string $table = 'announcements';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'title_bn', 'body', 'body_bn', 'audience', 'display_target',
        'is_published', 'starts_at', 'ends_at',
    ];

    protected array $casts = [
        'audience' => 'json',
        'is_published' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query
            ->whereRaw('(starts_at IS NULL OR starts_at <= ?)', [date('Y-m-d H:i:s')])
            ->whereRaw('(ends_at IS NULL OR ends_at >= ?)', [date('Y-m-d H:i:s')]);
    }

    public function scopeForHeader($query)
    {
        return $query->whereRaw("(display_target = ? OR display_target = ?)", ['header', 'both']);
    }

    public function scopeForNotification($query)
    {
        return $query->whereRaw("(display_target = ? OR display_target = ?)", ['notification', 'both']);
    }

    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'bn' && !empty($this->title_bn)) {
            return $this->title_bn;
        }
        return (string) $this->title;
    }

    public function localizedBody(): ?string
    {
        if (app()->getLocale() === 'bn' && !empty($this->body_bn)) {
            return $this->body_bn;
        }
        return $this->body;
    }
}
