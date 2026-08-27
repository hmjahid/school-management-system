<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Notice extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'title_bn', 'content', 'content_bn', 'pinned', 'audience'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('notices');
    }

    protected $fillable = [
        'title',
        'title_bn',
        'content',
        'content_bn',
        'attachments',
        'pinned',
        'audience',
        'created_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'audience' => 'array',
        'pinned' => 'boolean',
    ];

    public function localizedTitle(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->title_bn)) {
            return $this->title_bn;
        }

        return $this->title;
    }

    public function localizedContent(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->content_bn)) {
            return $this->content_bn;
        }

        return $this->content;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
