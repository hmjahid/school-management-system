<?php

namespace Tests\Unit\Models;

use App\Models\DashboardFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFavoriteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns(): void
    {
        $user = User::factory()->create();

        $favorite = DashboardFavorite::create([
            'user_id' => $user->id,
            'url' => '/admin/dashboard',
            'label' => 'Dashboard',
        ]);

        $this->assertDatabaseHas('dashboard_favorites', [
            'id' => $favorite->id,
            'user_id' => $user->id,
            'url' => '/admin/dashboard',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $favorite = DashboardFavorite::create([
            'user_id' => $user->id,
            'url' => '/admin/students',
        ]);

        $this->assertTrue($favorite->user->is($user));
    }

    /** @test */
    public function it_enforces_unique_url_per_user(): void
    {
        $user = User::factory()->create();
        $url = '/admin/unique-' . uniqid();

        DashboardFavorite::create(['user_id' => $user->id, 'url' => $url]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DashboardFavorite::create(['user_id' => $user->id, 'url' => $url]);
    }
}
