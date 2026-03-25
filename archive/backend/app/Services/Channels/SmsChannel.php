<?php

namespace App\Services\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Contracts\SmsService;

class SmsChannel
{
    /**
     * The SMS service implementation.
     *
     * @var \App\Contracts\SmsService
     */
    protected $smsService;

    /**
     * Create a new SMS channel instance.
     *
     * @param  \App\Contracts\SmsService  $smsService
     * @return void
     */
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send the given notification via SMS.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('sms', $notification)) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (is_string($message)) {
            $message = new \App\Notifications\Messages\SmsMessage($message);
        }

        $response = $this->smsService->send(
            $to, 
            $message->content,
            $message->from ?? null
        );

        // Log the SMS sending attempt
        Log::info('SMS notification sent', [
            'to' => $to,
            'message' => $message->content,
            'response' => $response,
        ]);
    }
}
