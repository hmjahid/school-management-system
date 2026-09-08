<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WebsiteMedia extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'category', 'file_path', 'mime_type', 'file_size'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('media');
    }

    protected $fillable = [
        'title',
        'category',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function url(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return url('storage/'.ltrim($this->file_path, '/'));
    }

    public function isImage(): bool
    {
        if ($this->mime_type !== null && str_starts_with($this->mime_type, 'image/')) {
            return true;
        }

        return in_array(
            strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'avif'],
            true,
        );
    }
}
