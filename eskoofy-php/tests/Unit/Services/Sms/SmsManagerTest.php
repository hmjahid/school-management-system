<?php
declare(strict_types=1);

namespace Tests\Unit\Services\Sms;

use App\Services\Sms\LogSmsService;
use App\Services\Sms\SmsManager;
use App\Services\Sms\TwilioSmsService;
use App\Services\Sms\VonageSmsService;
use Tests\TestCase;

class SmsManagerTest extends TestCase
{
    public function test_manager_returns_default_log_driver_by_default(): void
    {
        $manager = new SmsManager([
            'default' => 'log',
            'drivers' => ['log' => ['driver' => 'log']],
        ]);

        $this->assertSame('log', $manager->defaultDriver());
        $this->assertInstanceOf(LogSmsService::class, $manager->driver());
    }

    public function test_manager_returns_twilio_driver(): void
    {
        $manager = new SmsManager([
            'default' => 'twilio',
            'drivers' => [
                'twilio' => ['driver' => 'twilio', 'account_sid' => 'AC1'],
            ],
        ]);

        $service = $manager->driver('twilio');

        $this->assertInstanceOf(TwilioSmsService::class, $service);
        $this->assertSame('twilio', $service->name());
    }

    public function test_manager_resolves_nexmo_alias_to_vonage(): void
    {
        $manager = new SmsManager([
            'default' => 'nexmo',
            'aliases' => ['nexmo' => 'vonage'],
            'drivers' => [
                'nexmo' => ['driver' => 'nexmo', 'api_key' => 'k', 'api_secret' => 's'],
            ],
        ]);

        $service = $manager->driver('nexmo');

        $this->assertInstanceOf(VonageSmsService::class, $service);
        $this->assertSame('vonage', $service->name());
    }

    public function test_manager_returns_vonage_via_configured_driver_key(): void
    {
        $manager = new SmsManager([
            'default' => 'vonage',
            'drivers' => [
                'vonage' => ['driver' => 'vonage'],
            ],
        ]);

        $this->assertInstanceOf(VonageSmsService::class, $manager->driver('vonage'));
    }

    public function test_manager_caches_driver_instances(): void
    {
        $manager = new SmsManager([
            'default' => 'log',
            'drivers' => ['log' => ['driver' => 'log']],
        ]);

        $this->assertSame($manager->driver('log'), $manager->driver('log'));
    }

    public function test_manager_lists_all_drivers(): void
    {
        $manager = new SmsManager([
            'default' => 'log',
            'drivers' => [
                'log' => ['driver' => 'log'],
                'twilio' => ['driver' => 'twilio'],
            ],
        ]);

        $drivers = $manager->drivers();

        $this->assertCount(2, $drivers);
        $this->assertArrayHasKey('log', $drivers);
        $this->assertArrayHasKey('twilio', $drivers);
    }

    public function test_manager_throws_for_unknown_driver(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $manager = new SmsManager([
            'default' => 'nope',
            'drivers' => [],
        ]);

        $manager->driver('nope');
    }
}