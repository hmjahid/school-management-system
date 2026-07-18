<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdmissionTransactionSubmittedNotification extends Notification
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
            ->subject(__('Payment submitted — awaiting verification'))
            ->greeting(__('Hello :name,', ['name' => $this->admission->full_name]))
            ->line(__('We have received your payment details for admission application :num.', ['num' => $this->admission->application_number]))
            ->line(__('Method: :method', ['method' => strtoupper((string) $this->admission->payment_method)]))
            ->line(__('Transaction ID: :tid', ['tid' => $this->admission->transaction_id]))
            ->line(__('Our team will verify your payment shortly. You will receive an approval letter once it is confirmed.'))
            ->action(__('View status'), route('admissions.status', ['application_number' => $this->admission->application_number]));
    }
}