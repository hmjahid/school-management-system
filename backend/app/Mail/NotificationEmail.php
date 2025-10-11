<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The notification data.
     *
     * @var array
     */
    public $notification;

    /**
     * Create a new message instance.
     *
     * @param  array  $notification
     * @return void
     */
    public function __construct(array $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->notification['subject'] ?? 'New Notification';
        $content = $this->notification['content'] ?? '';
        $actionUrl = $this->notification['action_url'] ?? null;
        $data = $this->notification['data'] ?? [];

        // Add default styling
        $styles = [
            'body' => 'font-family: Arial, sans-serif; line-height: 1.6; color: #333;',
            'container' => 'max-width: 600px; margin: 0 auto; padding: 20px;',
            'header' => 'background-color: #4a86e8; color: white; padding: 20px; text-align: center;',
            'content' => 'background-color: #fff; padding: 30px;',
            'button' => 'display: inline-block; padding: 10px 20px; background-color: #4a86e8; color: white; text-decoration: none; border-radius: 4px;',
            'footer' => 'margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777;',
        ];

        // Build the email view
        return $this->subject($subject)
            ->view('emails.notification', [
                'styles' => $styles,
                'content' => new HtmlString($content),
                'actionUrl' => $actionUrl,
                'data' => $data,
            ]);
    }
}
