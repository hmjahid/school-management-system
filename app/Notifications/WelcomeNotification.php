<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => __('Welcome to :school', ['school' => config('app.name', 'SchoolEase')]),
            'message' => __('Your admin account is ready. Explore the dashboard, manage students, fees, and more.'),
            'url' => route('dashboard'),
        ];
    }
}
