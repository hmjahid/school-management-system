<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserWidgetPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserWidgetPreferenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns_and_casts(): void
    {
        $user = User::factory()->create();

        $pref = UserWidgetPreference::create([
            'user_id' => $user->id,
            'widget_id' => 'quick_stats',
            'enabled' => false,
            'position' => 3,
            'settings' => ['show_revenue' => false],
        ]);

        $this->assertDatabaseHas('user_widget_preferences', [
            'id' => $pref->id,
            'widget_id' => 'quick_stats',
        ]);

        $this->assertIsBool($pref->enabled);
        $this->assertIsInt($pref->position);
        $this->assertIsArray($pref->settings);
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $pref = UserWidgetPreference::create([
            'user_id' => $user->id,
            'widget_id' => 'revenue_chart',
        ]);

        $this->assertTrue($pref->user->is($user));
    }

    /** @test */
    public function it_returns_default_widgets(): void
    {
        $defaults = UserWidgetPreference::getDefaultWidgets();

        $this->assertArrayHasKey('quick_stats', $defaults);
        $this->assertArrayHasKey('recent_activity', $defaults);
    }

    /** @test */
    public function get_for_user_merges_defaults_with_saved_preferences(): void
    {
        $user = User::factory()->create();

        UserWidgetPreference::create([
            'user_id' => $user->id,
            'widget_id' => 'quick_stats',
            'enabled' => false,
            'position' => 9,
            'settings' => ['show_revenue' => false],
        ]);

        $widgets = UserWidgetPreference::getForUser($user->id);

        $quick = collect($widgets)->firstWhere('id', 'quick_stats');
        $this->assertFalse($quick['enabled']);
        $this->assertEquals(9, $quick['position']);

        $recent = collect($widgets)->firstWhere('id', 'recent_activity');
        $this->assertTrue($recent['enabled']);
    }

    /** @test */
    public function save_for_user_creates_or_updates_preferences(): void
    {
        $user = User::factory()->create();

        UserWidgetPreference::saveForUser($user->id, [
            ['id' => 'quick_stats', 'enabled' => false, 'position' => 1, 'settings' => []],
        ]);

        $this->assertDatabaseHas('user_widget_preferences', [
            'user_id' => $user->id,
            'widget_id' => 'quick_stats',
            'enabled' => false,
        ]);
    }
}
