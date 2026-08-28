<?php

namespace Tests\Feature;

use App\Models\DashboardFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFavoritesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_toggle_adds_and_removes_favorite(): void
    {
        $url = '/dashboard/students';

        $this->postJson(route('dashboard.favorites.toggle'), ['url' => $url, 'label' => 'Students'])
            ->assertOk()
            ->assertJson(['favorite' => true]);

        $this->assertDatabaseHas('dashboard_favorites', ['user_id' => $this->user->id, 'url' => $url]);

        $this->postJson(route('dashboard.favorites.toggle'), ['url' => $url, 'label' => 'Students'])
            ->assertOk()
            ->assertJson(['favorite' => false]);

        $this->assertDatabaseCount('dashboard_favorites', 0);
    }

    public function test_favorites_are_isolated_per_user(): void
    {
        $other = User::factory()->create();

        $this->postJson(route('dashboard.favorites.toggle'), ['url' => '/dashboard/exams', 'label' => 'Exams'])
            ->assertOk();

        $this->assertDatabaseHas('dashboard_favorites', ['user_id' => $this->user->id, 'url' => '/dashboard/exams']);
        $this->assertDatabaseMissing('dashboard_favorites', ['user_id' => $other->id, 'url' => '/dashboard/exams']);

        $this->actingAs($other)
            ->postJson(route('dashboard.favorites.toggle'), ['url' => '/dashboard/exams', 'label' => 'Exams'])
            ->assertOk()
            ->assertJson(['favorite' => true]);

        $this->assertDatabaseCount('dashboard_favorites', 2);
    }

    public function test_invalid_url_is_rejected(): void
    {
        $this->postJson(route('dashboard.favorites.toggle'), ['url' => 'https://evil.example.com', 'label' => 'Bad'])
            ->assertStatus(422);
    }

    public function test_favorites_pruned_beyond_limit(): void
    {
        for ($i = 0; $i < 15; $i++) {
            DashboardFavorite::create([
                'user_id' => $this->user->id,
                'url' => "/dashboard/page-{$i}",
                'label' => "Page {$i}",
            ]);
        }

        $this->postJson(route('dashboard.favorites.toggle'), ['url' => '/dashboard/students', 'label' => 'Students'])
            ->assertOk();

        $this->assertLessThanOrEqual(12, DashboardFavorite::where('user_id', $this->user->id)->count());
        $this->assertDatabaseHas('dashboard_favorites', ['user_id' => $this->user->id, 'url' => '/dashboard/students']);
    }
}
