<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdmissionSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public Admission $admission) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Admission application received'))
            ->greeting(__('Hello :name,', ['name' => $this->admission->full_name]))
            ->line(__('We received your admission application.'))
            ->line(__('Application number: :num', ['num' => $this->admission->application_number]))
            ->action(__('Track application status'), route('admissions.status', ['application_number' => $this->admission->application_number]))
            ->line(__('Please keep your application number for future reference.'));
    }
}
