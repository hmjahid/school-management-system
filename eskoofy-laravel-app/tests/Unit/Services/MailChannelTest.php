<?php

namespace Tests\Unit\Services;

use App\Mail\NotificationEmail;
use App\Services\Channels\MailChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailChannelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_mailable_through_the_mailer(): void
    {
        Mail::fake();

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification = null)
            {
                return $channel === 'mail' ? 'a@b.com' : null;
            }
        };

        $notification = new class extends Notification
        {
            public function toMail($notifiable)
            {
                return new NotificationEmail(['subject' => 'Hi', 'content' => 'Body']);
            }
        };

        $channel = new MailChannel;
        $channel->send($notifiable, $notification);

        Mail::assertSent(NotificationEmail::class);
    }

    #[Test]
    public function it_does_not_send_when_there_is_no_mail_route_and_no_mailable(): void
    {
        Mail::fake();

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification = null)
            {
                return null;
            }
        };

        $notification = new class extends Notification
        {
            public function toMail($notifiable)
            {
                return new MailMessage;
            }
        };

        $channel = new MailChannel;
        $channel->send($notifiable, $notification);

        Mail::assertNothingSent();
    }
}
