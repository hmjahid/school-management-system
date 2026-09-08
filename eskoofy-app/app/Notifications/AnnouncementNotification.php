<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->announcement->localizedTitle(),
            'message' => \Illuminate\Support\Str::limit(strip_tags($this->announcement->localizedBody() ?? ''), 200),
            'url' => route('dashboard.announcements.edit', $this->announcement),
            'type' => 'announcement',
        ];
    }
}
