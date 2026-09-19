<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdmissionStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Admission $admission,
        public string $oldStatus,
        public string $newStatus,
        public ?string $message = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('Admission status update'))
            ->greeting(__('Hello :name,', ['name' => $this->admission->full_name]))
            ->line(__('Your admission application status has been updated.'))
            ->line(__('Application number: :num', ['num' => $this->admission->application_number]))
            ->line(__('New status: :status', ['status' => $this->admission->status_label]))
            ->action(__('View status'), route('admissions.status', ['application_number' => $this->admission->application_number]));

        if ($this->message) {
            $mail->line($this->message);
        }

        if ($this->newStatus === Admission::STATUS_REJECTED && $this->admission->rejection_reason) {
            $mail->line(__('Reason: :reason', ['reason' => $this->admission->rejection_reason]));
        }

        return $mail;
    }
}
