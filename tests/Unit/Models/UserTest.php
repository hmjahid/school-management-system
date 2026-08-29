<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_auto_assigns_student_role_on_create(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $studentRole = Role::where('name', 'student')->first();
        $this->assertNotNull($studentRole);
        $this->assertEquals($studentRole->id, $user->role_id);
    }

    /** @test */
    public function create_with_credential_strips_role_id_from_mass_assignment(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::createWithCredential([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $this->assertEquals($adminRole->id, $user->fresh()->role_id);
    }

    /** @test */
    public function create_with_credential_works_without_role_id(): void
    {
        $user = User::createWithCredential([
            'name' => 'No Role User',
            'email' => 'norole@example.com',
            'password' => 'password',
        ]);

        $this->assertNotNull($user->role_id);
    }

    /** @test */
    public function it_hashes_password_on_assignment(): void
    {
        $user = User::create([
            'name' => 'Hash Test',
            'email' => 'hash@example.com',
            'password' => 'plaintext-password',
        ]);

        $this->assertNotEquals('plaintext-password', $user->getRawOriginal('password'));
        $this->assertTrue(password_verify('plaintext-password', $user->getRawOriginal('password')));
    }

    /** @test */
    public function it_returns_role_id_for_existing_role(): void
    {
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $roleId = User::roleIdFor('teacher');

        $this->assertNotNull($roleId);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_role(): void
    {
        $roleId = User::roleIdFor('nonexistent_role');

        $this->assertNull($roleId);
    }

    /** @test */
    public function it_generates_profile_photo_url_from_initials(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ]);

        $url = $user->profile_photo_url;

        $this->assertStringContainsString('ui-avatars.com', $url);
        $this->assertStringContainsString('J+D', $url);
    }

    /** @test */
    public function it_uses_uploaded_photo_for_profile_url(): void
    {
        $user = User::create([
            'name' => 'Photo User',
            'email' => 'photo@example.com',
            'password' => 'password',
            'photo' => 'photos/user.jpg',
        ]);

        $this->assertStringContainsString('storage/photos/user.jpg', $user->profile_photo_url);
    }

    /** @test */
    public function has_permission_to_returns_false_for_undefined_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPermissionTo('nonexistent_permission'));
    }

    /** @test */
    public function it_creates_token_pair_with_access_and_refresh_tokens(): void
    {
        $user = User::factory()->create();

        $tokens = $user->createTokenPair();

        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);
        $this->assertEquals('Bearer', $tokens['token_type']);
        $this->assertIsInt($tokens['expires_in']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_revokes_all_tokens(): void
    {
        $user = User::factory()->create();
        $user->createTokenPair();
        $user->createTokenPair();

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $user->revokeAllTokens();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** @test */
    public function it_revokes_other_tokens_keeping_current(): void
    {
        $user = User::factory()->create();
        $tokens1 = $user->createTokenPair();
        $user->createTokenPair();

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $currentToken = $user->tokens()->first();
        $user->revokeOtherTokens($currentToken);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertEquals($currentToken->id, $user->tokens()->first()->id);
    }

    /** @test */
    public function has_any_permission_accepts_array(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasAnyPermission(['perm1', 'perm2', 'perm3']));
    }

    /** @test */
    public function has_any_permission_accepts_single_string(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasAnyPermission('single_permission'));
    }
}
