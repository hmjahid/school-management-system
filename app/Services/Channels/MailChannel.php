<?php

namespace App\Services\Channels;

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toMail($notifiable);

        if (! $notifiable->routeNotificationFor('mail', $notification) && ! $message instanceof Mailable) {
            return;
        }

        $mailer = $message->mailer ?? null;
        $mailable = $this->buildMailable(
            $mailer, $notifiable, $notification, $message
        );

        $mailer = $mailer ?? $this->mailer;

        $response = Mail::mailer($mailer)->send(
            $mailable,
            [],
            function ($m) use ($notifiable, $notification, $message) {
                $recipients = empty($message->to)
                    ? $notifiable->routeNotificationFor('mail', $notification)
                    : $message->to;

                if (! empty($message->from)) {
                    $m->from($message->from[0], isset($message->from[1]) ? $message->from[1] : null);
                }

                $m->to($recipients)
                  ->subject($message->subject ?: 
                      Str::title(Str::snake(class_basename($notification), ' '))
                  );

                if (! empty($message->attachments)) {
                    foreach ($message->attachments as $attachment) {
                        $m->attach($attachment['file'], $attachment['options']);
                    }
                }

                if (! empty($message->rawAttachments)) {
                    foreach ($message->rawAttachments as $attachment) {
                        $m->attachData(
                            $attachment['data'],
                            $attachment['name'],
                            $attachment['options']
                        );
                    }
                }

                if ($message->cc) {
                    $m->cc($message->cc[0], isset($message->cc[1]) ? $message->cc[1] : null);
                }

                if ($message->bcc) {
                    $m->bcc($message->bcc[0], isset($message->bcc[1]) ? $message->bcc[1] : null);
                }

                if ($message->replyTo) {
                    $m->replyTo(
                        $message->replyTo[0],
                        isset($message->replyTo[1]) ? $message->replyTo[1] : null
                    );
                }
            }
        );

        // Log the email sending attempt
        Log::info('Email notification sent', [
            'to' => $recipients,
            'subject' => $message->subject,
            'notification' => get_class($notification),
        ]);
    }

    /**
     * Build the mailable for the given notification.
     *
     * @param  string  $mailer
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return \Illuminate\Mail\Mailable
     */
    protected function buildMailable($mailer, $notifiable, $notification, $message)
    {
        $mailable = new Mailable();
        
        if ($message instanceof Mailable) {
            return $message;
        }

        return $mailable
            ->mailer($mailer)
            ->subject($message->subject)
            ->view($message->view, $message->viewData);
    }
}
