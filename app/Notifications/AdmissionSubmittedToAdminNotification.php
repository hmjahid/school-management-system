<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdmissionSubmittedToAdminNotification extends Notification
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
            ->subject(__('New admission application received'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('A new admission application has been submitted and requires review.'))
            ->line(__('Applicant: :name', ['name' => $this->admission->full_name]))
            ->line(__('Application number: :num', ['num' => $this->admission->application_number]))
            ->line(__('Email: :email', ['email' => $this->admission->email]))
            ->line(__('Phone: :phone', ['phone' => $this->admission->phone]))
            ->action(__('Review application'), route('dashboard.admissions.show', $this->admission->id));
    }
}
