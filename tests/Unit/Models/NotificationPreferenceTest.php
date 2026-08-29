<?php

namespace Tests\Unit\Models;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns_and_casts(): void
    {
        $user = User::factory()->create();

        $pref = NotificationPreference::create([
            'user_id' => $user->id,
            'notification_type' => 'payment_received',
            'email' => false,
            'sms' => true,
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'id' => $pref->id,
            'notification_type' => 'payment_received',
        ]);
        $this->assertIsBool($pref->email);
        $this->assertIsBool($pref->sms);
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $pref = NotificationPreference::create([
            'user_id' => $user->id,
            'notification_type' => 'refund_created',
        ]);

        $this->assertTrue($pref->user->is($user));
    }

    /** @test */
    public function it_exposes_valid_types_and_channels(): void
    {
        $this->assertContains('refund_created', NotificationPreference::getAvailableTypes());
        $this->assertContains('email', NotificationPreference::getAvailableChannels());
        $this->assertTrue(NotificationPreference::isValidType('payment_failed'));
        $this->assertFalse(NotificationPreference::isValidType('nope'));
        $this->assertTrue(NotificationPreference::isValidChannel('sms'));
    }

    /** @test */
    public function it_sets_and_gets_user_preference(): void
    {
        $user = User::factory()->create();
        $type = 'refund_status_updated';

        $this->assertTrue(
            NotificationPreference::setUserPreference($user->id, $type, 'sms', true)
        );

        $this->assertTrue(
            NotificationPreference::getUserPreference($user->id, $type, 'sms')
        );

        // 'payment_received' defaults sms to false when no preference exists
        $this->assertFalse(
            NotificationPreference::getUserPreference($user->id, 'payment_received', 'sms')
        );
    }

    /** @test */
    public function getUserPreferences_falls_back_to_defaults(): void
    {
        $user = User::factory()->create();

        $prefs = NotificationPreference::getUserPreferences($user->id);

        $this->assertArrayHasKey('refund_created', $prefs);
        $this->assertArrayHasKey('email', $prefs['refund_created']);
    }
}
