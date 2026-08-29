<?php

namespace Tests\Unit\Services;

use App\Contracts\SmsService;
use App\Notifications\Messages\SmsMessage;
use App\Services\Channels\SmsChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SmsChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_through_the_sms_service_and_logs(): void
    {
        Log::spy();

        $smsService = Mockery::mock(SmsService::class);
        $smsService->shouldReceive('send')
            ->once()
            ->with('+8801700000000', 'Hello world', ['from' => 'School'])
            ->andReturn(true);

        $notifiable = new class {
            public function routeNotificationFor($channel, $notification = null)
            {
                return $channel === 'sms' ? '+8801700000000' : null;
            }
        };

        $notification = new class extends Notification {
            public function toSms($notifiable)
            {
                return (new SmsMessage('Hello world'))->from('School');
            }
        };

        $channel = new SmsChannel($smsService);
        $channel->send($notifiable, $notification);

        Log::shouldHaveReceived('info')->once();
    }

    /** @test */
    public function it_does_nothing_when_there_is_no_sms_route(): void
    {
        $smsService = Mockery::mock(SmsService::class);
        $smsService->shouldNotReceive('send');

        $notifiable = new class {
            public function routeNotificationFor($channel, $notification = null)
            {
                return null;
            }
        };

        $notification = new class extends Notification {
            public function toSms($notifiable)
            {
                return new SmsMessage('Hello');
            }
        };

        $channel = new SmsChannel($smsService);
        $channel->send($notifiable, $notification);

        $this->assertTrue(true);
    }
}
