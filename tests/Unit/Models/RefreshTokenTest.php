<?php

namespace Tests\Unit\Models;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private function makeToken(User $user, array $attributes = []): RefreshToken
    {
        return $user->refreshTokens()->create(array_merge([
            'expires_at' => now()->addDays(30),
        ], $attributes));
    }

    /** @test */
    public function it_persists_required_columns_and_auto_generates_token(): void
    {
        $user = User::factory()->create();
        $token = $this->makeToken($user);

        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $token->id,
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($token->token);
        $this->assertNotEquals('', $token->token);
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $token = $this->makeToken($user);

        $this->assertTrue($token->user->is($user));
    }

    /** @test */
    public function is_expired_reflects_expiry(): void
    {
        $user = User::factory()->create();

        $valid = $this->makeToken($user, ['expires_at' => now()->addDay()]);
        $this->assertFalse($valid->isExpired());

        $expired = $this->makeToken($user, ['expires_at' => now()->subDay()]);
        $this->assertTrue($expired->isExpired());
    }

    /** @test */
    public function mark_as_used_sets_last_used_at(): void
    {
        $user = User::factory()->create();
        $token = $this->makeToken($user);

        $token->markAsUsed();
        $this->assertNotNull($token->fresh()->last_used_at);
    }
}
