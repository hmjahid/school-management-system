<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Announcement extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'title_bn', 'body', 'body_bn', 'audience', 'display_target', 'is_published', 'starts_at', 'ends_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('announcements');
    }

    protected $fillable = [
        'title',
        'title_bn',
        'body',
        'body_bn',
        'audience',
        'display_target',
        'is_published',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'audience' => 'array',
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
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function localizedTitle(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->title_bn)) {
            return $this->title_bn;
        }

        return $this->title;
    }

    public function localizedBody(): ?string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->body_bn)) {
            return $this->body_bn;
        }

        return $this->body;
    }

    public function scopeForHeader($query)
    {
        return $query->where('display_target', 'header')->orWhere('display_target', 'both');
    }

    public function scopeForNotification($query)
    {
        return $query->where('display_target', 'notification')->orWhere('display_target', 'both');
    }

    public function dispatchNotifications(): void
    {
        if (! in_array($this->display_target, ['notification', 'both']) || ! $this->is_published) {
            return;
        }

        $query = \App\Models\User::query();

        $audiences = is_array($this->audience) ? $this->audience : [$this->audience];

        if (in_array('all', $audiences)) {
            $query->whereHas('schoolRole', fn ($q) => $q->whereIn('name', ['student', 'parent', 'teacher']));
        } else {
            $query->whereHas('schoolRole', fn ($q) => $q->whereIn('name', $audiences));
        }

        $notification = new \App\Notifications\AnnouncementNotification($this);

        $query->chunk(100, function ($users) use ($notification) {
            Notification::send($users, $notification);
        });
    }
}
