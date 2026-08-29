<?php

namespace Tests\Unit\Services;

use App\Models\AcademicSession;
use App\Models\PaymentGateway;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\SetupChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SetupChecklistServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeWebsiteSetting(array $overrides = []): WebsiteSetting
    {
        return WebsiteSetting::create(array_merge([
            'school_name' => 'Test School',
            'address' => 'Dhaka',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'postal_code' => '1000',
            'phone' => '01700000000',
            'email' => 'school@example.com',
        ], $overrides));
    }

    /** @test */
    public function it_returns_six_checklist_items(): void
    {
        $service = app(SetupChecklistService::class);
        $items = $service->items();

        $this->assertCount(6, $items);

        $keys = array_column($items, 'key');
        $this->assertContains('school_info', $keys);
        $this->assertContains('timezone', $keys);
        $this->assertContains('academic_session', $keys);
        $this->assertContains('classes', $keys);
        $this->assertContains('teachers', $keys);
        $this->assertContains('payment', $keys);
    }

    /** @test */
    public function it_returns_zero_percent_when_nothing_is_configured(): void
    {
        $service = app(SetupChecklistService::class);

        $this->assertEquals(0, $service->completionPercent());
        $this->assertFalse($service->isComplete());
    }

    /** @test */
    public function it_detects_completed_school_info(): void
    {
        $this->makeWebsiteSetting(['school_name' => 'Test School', 'established_year' => 2020]);

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());

        $schoolInfo = $items->firstWhere('key', 'school_info');
        $this->assertTrue($schoolInfo['done']);
    }

    /** @test */
    public function it_detects_completed_academic_session(): void
    {
        AcademicSession::factory()->create();

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());

        $sessionItem = $items->firstWhere('key', 'academic_session');
        $this->assertTrue($sessionItem['done']);
    }

    /** @test */
    public function it_detects_completed_classes(): void
    {
        SchoolClass::factory()->create();

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());

        $classesItem = $items->firstWhere('key', 'classes');
        $this->assertTrue($classesItem['done']);
    }

    /** @test */
    public function it_detects_completed_teachers(): void
    {
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());

        $teachersItem = $items->firstWhere('key', 'teachers');
        $this->assertTrue($teachersItem['done']);
    }

    /** @test */
    public function it_detects_completed_payment(): void
    {
        PaymentGateway::create([
            'name' => 'bKash',
            'code' => 'bkash',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
        ]);

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());

        $paymentItem = $items->firstWhere('key', 'payment');
        $this->assertTrue($paymentItem['done']);
    }

    /** @test */
    public function it_returns_one_hundred_percent_when_all_complete(): void
    {
        $this->makeWebsiteSetting(['school_name' => 'Test School', 'established_year' => 2020, 'timezone' => 'Asia/Dhaka']);
        AcademicSession::factory()->create();
        SchoolClass::factory()->create();

        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('teacher');

        PaymentGateway::create([
            'name' => 'bKash',
            'code' => 'bkash',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
        ]);

        $service = app(SetupChecklistService::class);

        $this->assertTrue($service->isComplete());
        $this->assertEquals(100, $service->completionPercent());
    }

    /** @test */
    public function it_detects_valid_timezone(): void
    {
        $this->makeWebsiteSetting(['school_name' => 'TZ Test', 'established_year' => 2020, 'timezone' => 'Asia/Dhaka']);

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());

        $timezoneItem = $items->firstWhere('key', 'timezone');
        $this->assertTrue($timezoneItem['done']);
    }

    /** @test */
    public function it_rejects_invalid_timezone(): void
    {
        $this->makeWebsiteSetting(['school_name' => 'TZ Test', 'established_year' => 2020, 'timezone' => 'Invalid/Zone']);

        $service = app(SetupChecklistService::class);
        $items = collect($service->items());

        $timezoneItem = $items->firstWhere('key', 'timezone');
        $this->assertFalse($timezoneItem['done']);
    }
}
