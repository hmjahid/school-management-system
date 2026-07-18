<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdmissionPaymentVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(public Admission $admission, public string $outcome) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('Admission payment update'))
            ->greeting(__('Hello :name,', ['name' => $this->admission->full_name]))
            ->line(__('Payment for application :num has been processed.', ['num' => $this->admission->application_number]));

        if ($this->outcome === 'verified') {
            $mail->line(__('Your payment has been verified successfully.'))
                ->line(__('You can download your admission approval letter from the status page.'))
                ->action(__('View status'), route('admissions.status', ['application_number' => $this->admission->application_number]));
        } else {
            $mail->line(__('Your payment could not be verified.'))
                ->line(__('Reason: :note', ['note' => $this->admission->payment_note ?: __('Please contact the school office.')]))
                ->action(__('View status'), route('admissions.status', ['application_number' => $this->admission->application_number]));
        }

        return $mail;
    }
}