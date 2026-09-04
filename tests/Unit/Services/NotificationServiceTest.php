<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email' => 'notify@example.com'], $attrs));
    }

    protected function getProtected(object $obj, string $prop)
    {
        $r = new \ReflectionProperty($obj, $prop);
        $r->setAccessible(true);

        return $r->getValue($obj);
    }

    #[Test]
    public function it_returns_self_from_fluent_setters(): void
    {
        $service = new NotificationService;

        $this->assertSame($service, $service->type('info'));
        $this->assertSame($service, $service->subject('Subject'));
        $this->assertSame($service, $service->content('Body'));
        $this->assertSame($service, $service->actionUrl('https://example.com'));
        $this->assertSame($service, $service->icon('star'));
        $this->assertSame($service, $service->priority(5));
        $this->assertSame($service, $service->category('system'));
        $this->assertSame($service, $service->with(['a' => 'b']));
        $this->assertSame($service, $service->via(['database']));
        $this->assertSame($service, $service->to($this->makeUser()));
    }

    #[Test]
    public function it_throws_when_sending_without_recipients(): void
    {
        $service = (new NotificationService)
            ->type('info')
            ->content('Body');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No recipients specified.');

        $service->send();
    }

    #[Test]
    public function it_throws_when_sending_without_type(): void
    {
        $service = (new NotificationService)
            ->content('Body')
            ->to($this->makeUser());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Notification type is required.');

        $service->send();
    }

    #[Test]
    public function it_throws_when_sending_without_content_or_template(): void
    {
        $service = (new NotificationService)
            ->type('info')
            ->to($this->makeUser());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Notification content or template is required.');

        $service->send();
    }

    #[Test]
    public function it_accepts_user_array_and_collection_as_recipients(): void
    {
        $u1 = $this->makeUser(['email' => 'a@example.com']);
        $u2 = $this->makeUser(['email' => 'b@example.com']);
        $collection = new \Illuminate\Database\Eloquent\Collection([$u1, $u2]);

        $service = new NotificationService;

        $service->to([$u1, $u2]);
        $this->assertCount(2, $this->getProtected($service, 'recipients'));

        $service->to($collection);
        $this->assertCount(2, $this->getProtected($service, 'recipients'));

        $service->to($u1, $u2);
        $this->assertCount(2, $this->getProtected($service, 'recipients'));
    }

    #[Test]
    public function it_stores_a_database_notification_when_sent_via_database_channel(): void
    {
        $user = $this->makeUser();
        $service = (new NotificationService)
            ->type('info')
            ->subject('Hello')
            ->content('World')
            ->via(['database'])
            ->to($user);

        $responses = $service->send();

        $this->assertCount(1, $responses);
        $this->assertTrue($responses[0]['success']);
        $this->assertSame('database', $responses[0]['channel']);
        $this->assertSame($user->id, $responses[0]['user_id']);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => 'info',
        ]);
        $this->assertCount(1, $user->notifications);
    }

    #[Test]
    public function it_sends_mail_through_the_mailer_and_logs_it(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $service = (new NotificationService)
            ->type('info')
            ->subject('Mail Subject')
            ->content('Mail Body')
            ->via(['mail'])
            ->to($user);

        $responses = $service->send();

        $this->assertTrue($responses[0]['success']);
        $this->assertSame('mail', $responses[0]['channel']);

        Mail::assertSent(\App\Mail\NotificationEmail::class);
        $this->assertDatabaseHas('notification_logs', [
            'notifiable_id' => $user->id,
            'channel' => 'mail',
            'type' => 'info',
        ]);
    }

    #[Test]
    public function it_marks_a_notification_as_read(): void
    {
        $user = $this->makeUser();
        $notification = $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'info',
            'data' => ['subject' => 'Hi'],
            'read_at' => null,
        ]);

        $service = new NotificationService;

        $this->assertTrue($service->markAsRead($notification->id, $user->id));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertFalse($service->markAsRead($notification->id, $user->id));
    }

    #[Test]
    public function it_marks_all_notifications_as_read_and_counts_unread(): void
    {
        $user = $this->makeUser();
        for ($i = 0; $i < 3; $i++) {
            $user->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'info',
                'data' => ['subject' => 'Hi'],
                'read_at' => null,
            ]);
        }

        $service = new NotificationService;

        $this->assertSame(3, $service->getUnreadCount($user->id));
        $this->assertSame(3, $service->markAllAsRead($user->id));
        $this->assertSame(0, $service->getUnreadCount($user->id));
    }

    #[Test]
    public function it_returns_paginated_notifications_for_a_user(): void
    {
        $user = $this->makeUser();
        for ($i = 0; $i < 3; $i++) {
            $user->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'info',
                'data' => ['subject' => 'Hi'],
                'read_at' => null,
            ]);
        }

        $service = new NotificationService;

        $this->assertCount(2, $service->getNotifications($user->id, 2, 0));
        $this->assertCount(1, $service->getNotifications($user->id, 2, 2));
    }
}
