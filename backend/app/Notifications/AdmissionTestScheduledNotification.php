<?php

namespace App\Notifications;

use App\Models\Admission;
use App\Models\AdmissionTest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdmissionTestScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(public Admission $admission, public AdmissionTest $test) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->test->scheduled_at?->format('Y-m-d H:i') ?? '—';

        $mail = (new MailMessage)
            ->subject(__('Admission test scheduled'))
            ->greeting(__('Hello :name,', ['name' => $this->admission->full_name]))
            ->line(__('A test has been scheduled for your admission application.'))
            ->line(__('Application number: :num', ['num' => $this->admission->application_number]))
            ->line(__('Date & time: :when', ['when' => $when]))
            ->line(__('Venue: :venue', ['venue' => $this->test->venue ?: '—']))
            ->action(__('View status'), route('admissions.status', ['application_number' => $this->admission->application_number]));

        if ($this->test->notes) {
            $mail->line(__('Notes: :notes', ['notes' => $this->test->notes]));
        }

        return $mail;
    }
}
